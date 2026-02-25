<?php

namespace App\Http\Controllers;

use App\Models\B2BAmPointSummary;
use App\Models\B2BClient;
use App\Models\Prize;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class B2BPortalController extends Controller
{
    private const CAMPAIGN_PACKAGE_NAMES = [
        // 'paket rendang',
    ];

    public function inputClient()
    {
        $clients = B2BClient::where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('b2b.input-client', compact('clients'));
    }

    public function storeClient(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'mobile_phone' => ['required', 'string', 'max:30', 'regex:/^62\d{9,14}$/'],
            'email' => 'required|email|max:255',
            'nama' => 'nullable|string|max:255',
            'sector' => 'nullable|string|max:255',
            'myads_account' => 'required|string|max:255|unique:b2b_clients,myads_account,NULL,id,user_id,' . Auth::id(),
            'remarks' => 'nullable|string|max:1000',
        ], [
            'mobile_phone.regex' => 'No HP harus diawali 62 dan berisi angka.',
        ]);

        B2BClient::create([
            'user_id' => Auth::id(),
            'company_name' => trim($request->company_name),
            'customer_phone' => trim($request->mobile_phone),
            'customer_email' => strtolower(trim($request->email)),
            'customer_name' => $request->nama ? trim($request->nama) : null,
            'sector' => $request->sector ? trim($request->sector) : null,
            'myads_account' => strtolower(trim($request->myads_account)),
            'remarks' => $request->remarks ? trim($request->remarks) : null,
            // Fill legacy columns so insert works on old schema where these fields are NOT NULL.
            'client_name' => $request->nama ? trim($request->nama) : trim($request->company_name),
            'client_email' => strtolower(trim($request->myads_account)),
            'client_phone' => trim($request->mobile_phone),
        ]);

        return redirect()->route('b2b.clients.index')->with('success', 'Klien berhasil ditambahkan.');
    }

    public function performance(Request $request)
    {
        [$monthDate, $startDate, $endDate, $monthValue, $monthLabel] = $this->resolveMonth($request->get('month'));
        $summary = $this->buildUserPerformance(Auth::id(), $monthDate, $startDate, $endDate);

        return view('b2b.performance', [
            'monthValue' => $monthValue,
            'monthLabel' => $monthLabel,
            'summary' => $summary,
        ]);
    }

    public function leaderboard(Request $request)
    {
        [$monthDate, , , $monthValue, $monthLabel] = $this->resolveMonth($request->get('month'));
        $periodMonth = $monthDate->copy()->startOfMonth()->toDateString();

        $users = User::query()
            ->where('role', 'b2b')
            ->select('id', 'name', 'email')
            ->get();

        $summaryByUser = B2BAmPointSummary::query()
            ->whereDate('period_month', $periodMonth)
            ->get()
            ->keyBy('user_id');

        $rows = $users->map(function ($user) use ($summaryByUser) {
            $summary = $summaryByUser->get($user->id);
            $totalTopup = (float) ($summary->total_topup ?? 0);
            $campaignPoint = (int) ($summary->campaign_point ?? 0);
            // Samakan dengan performansi: floor(sum poin sisa bulan ini) = floor((topup/1jt) + poin paket)
            $basePoint = (int) floor(($totalTopup / 1000000) + $campaignPoint);
            $redeemPoint = (int) ($summary->total_redeem_point ?? 0);
            $finalPoint = max($basePoint - $redeemPoint, 0);

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'client_count' => (int) ($summary->client_count ?? 0),
                'total_topup' => $totalTopup,
                'points' => $finalPoint,
                'point_decimal' => (float) ($summary->point_decimal ?? 0),
                'carry_out_decimal' => (float) ($summary->carry_out_decimal ?? 0),
                'campaign_point' => $campaignPoint,
                'total_redeem_point' => $redeemPoint,
            ];
        })->sortBy([
            ['points', 'desc'],
            ['total_topup', 'desc'],
        ])->values();

        $rankedRows = $rows->values()->map(function ($row, $index) {
            $row['rank'] = $index + 1;
            return $row;
        });

        return view('b2b.leaderboard', [
            'monthValue' => $monthValue,
            'monthLabel' => $monthLabel,
            'rows' => $rankedRows,
        ]);
    }

    public function rewards(Request $request)
    {
        [$monthDate, $startDate, $endDate, $monthValue, $monthLabel] = $this->resolveMonth($request->get('month'));
        $summary = $this->buildUserPerformance(Auth::id(), $monthDate, $startDate, $endDate);

        $prizes = Prize::query()->orderBy('point', 'desc')->get();
        $redeem = DB::table('redeem_prizes_am_level_up')->where('user_id', Auth::id())->first();

        $availablePoints = max($summary['points'], 0);

        return view('b2b.rewards', [
            'monthValue' => $monthValue,
            'monthLabel' => $monthLabel,
            'summary' => $summary,
            'prizes' => $prizes,
            'redeem' => $redeem,
            'availablePoints' => $availablePoints,
        ]);
    }

    public function redeemReward(Request $request)
    {
        $request->validate([
            'prize_id' => 'required|integer|exists:prizes_am_level_up,id',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $prize = Prize::query()
                    ->where('id', $request->prize_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($prize->stock <= 0) {
                    throw new \RuntimeException('Stok hadiah habis.');
                }

                $alreadyRedeem = DB::table('redeem_prizes_am_level_up')
                    ->where('user_id', Auth::id())
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyRedeem) {
                    throw new \RuntimeException('Anda sudah melakukan redeem hadiah.');
                }

                [$monthDate, $startDate, $endDate] = $this->resolveMonth(now()->format('Y-m'));
                $summary = $this->buildUserPerformance(Auth::id(), $monthDate, $startDate, $endDate);
                $availablePoints = max($summary['points'], 0);

                if ($availablePoints < (int) $prize->point) {
                    throw new \RuntimeException('Poin tidak cukup untuk redeem hadiah ini.');
                }

                $prize->decrement('stock');

                DB::table('redeem_prizes_am_level_up')->insert([
                    'user_id' => Auth::id(),
                    'prize_id' => $prize->id,
                    'point_used' => (int) $prize->point,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\Throwable $e) {
            return redirect()->route('b2b.rewards')->with('error', $e->getMessage());
        }

        return redirect()->route('b2b.rewards')->with('success', 'Redeem hadiah berhasil.');
    }

    public function syncDailySummaryForAllUsers(?string $month = null): array
    {
        [$monthDate, $startDate, $endDate] = $this->resolveMonth($month);

        $users = User::query()
            ->where('role', 'b2b')
            ->select('id')
            ->get();

        $processed = 0;

        foreach ($users as $user) {
            $this->syncMonthlySummaryForUser($user->id, $monthDate, $startDate, $endDate);
            $processed++;
        }

        return [
            'month' => $monthDate->format('Y-m'),
            'processed_users' => $processed,
        ];
    }

    private function buildUserPerformance(int $userId, Carbon $monthDate, string $startDate, string $endDate): array
    {
        $clients = B2BClient::query()
            ->where('user_id', $userId)
            ->orderBy('company_name')
            ->get();

        $emails = $clients->pluck('myads_account')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $topupByEmail = $this->queryTopupByEmails($emails, $startDate, $endDate);
        $packagePointByEmail = $this->queryCampaignPointMapByEmails($emails, $startDate, $endDate);

        $clientRows = $clients->map(function ($client) use ($topupByEmail, $packagePointByEmail) {
            $email = strtolower(trim((string) $client->myads_account));
            $topup = (int) ($topupByEmail[$email] ?? 0);
            $pointPackage = (int) ($packagePointByEmail[$email] ?? 0);
            $pointFromTopup = (int) ($topup / 1000000);
            $pointSisa = (int) $pointFromTopup;

            return [
                'company_name' => $client->company_name,
                'customer_name' => $client->customer_name,
                'customer_email' => $client->customer_email,
                'customer_phone' => $client->customer_phone,
                'sector' => $client->sector,
                'myads_account' => $client->myads_account,
                'remarks' => $client->remarks,
                'topup' => $topup,
                'point_package' => $pointPackage,
                'point_decimal' => $pointFromTopup,
                'point_sisa' => $pointSisa,
            ];
        });

        $summary = $this->syncMonthlySummaryForUser(
            $userId,
            $monthDate,
            $startDate,
            $endDate,
            $clients->count(),
            $emails,
            $topupByEmail
        );
        $pointsFloor = ((int) $summary->point_rounded) + ((int) $summary->campaign_point);
        $totalRedeemPoint = (int) $summary->total_redeem_point;
        $finalPoints = max($pointsFloor - $totalRedeemPoint, 0);

        return [
            'client_count' => (int) $summary->client_count,
            'total_topup' => (float) $summary->total_topup,
            'points' => $finalPoints,
            'points_floor' => $pointsFloor,
            'point_decimal' => (float) $summary->point_decimal,
            'campaign_point' => (int) $summary->campaign_point,
            'total_redeem_point' => $totalRedeemPoint,
            'carry_in_amount' => (float) $summary->carry_in_amount,
            'carry_out_amount' => (float) $summary->carry_out_amount,
            'carry_out_decimal' => (float) $summary->carry_out_decimal,
            'clients' => $clientRows,
        ];
    }

    private function syncMonthlySummaryForUser(
        int $userId,
        Carbon $monthDate,
        string $startDate,
        string $endDate,
        ?int $clientCount = null,
        ?array $emails = null,
        ?array $topupByEmail = null
    ): B2BAmPointSummary {
        $monthStart = $monthDate->copy()->startOfMonth();

        if ($clientCount === null || $emails === null || $topupByEmail === null) {
            [$clientCount, $emails, $topupByEmail] = $this->getMonthlyClientData($userId, $startDate, $endDate);
        }

        $totalTopup = (float) array_sum($topupByEmail);
        $monthlyPointDecimal = $totalTopup / 1000000;
        $monthlyPointRounded = (int) floor($monthlyPointDecimal);

        $previousSummary = $this->getPreviousMonthSummary($userId, $monthStart);
        $previousPointRounded = (int) ($previousSummary->point_rounded ?? 0);
        $previousCampaignPoint = (int) ($previousSummary->campaign_point ?? 0);
        $carryInPoint = $previousPointRounded + $previousCampaignPoint;

        // Carry antar bulan adalah poin integer.
        // Untuk kolom amount, konversi ke nominal ekuivalen (1 poin = 1.000.000).
        $carryInAmount = (float) ($carryInPoint * 1000000);
        $totalAmountForPoint = $totalTopup;
        $pointDecimal = $monthlyPointDecimal;
        $carryOutAmount = 0.0;
        $carryOutDecimal = 0.0;
        $campaignPointFromTable = $this->queryCampaignPointByEmails($emails, $startDate, $endDate);
        $campaignPoint = (int) $campaignPointFromTable;
        $pointRounded = $carryInPoint + $monthlyPointRounded;
        $totalRedeemPoint = (int) DB::table('redeem_prizes_am_level_up')
            ->where('user_id', $userId)
            ->sum('point_used');

        return B2BAmPointSummary::updateOrCreate(
            [
                'user_id' => $userId,
                'period_month' => $monthStart->toDateString(),
            ],
            [
                'client_count' => $clientCount,
                'total_topup' => $totalTopup,
                'carry_in_amount' => $carryInAmount,
                'total_amount_for_point' => $totalAmountForPoint,
                'point_decimal' => $pointDecimal,
                'point_rounded' => $pointRounded,
                'campaign_point' => $campaignPoint,
                'total_redeem_point' => $totalRedeemPoint,
                'carry_out_amount' => $carryOutAmount,
                'carry_out_decimal' => $carryOutDecimal,
            ]
        );
    }

    private function getPreviousMonthSummary(int $userId, Carbon $currentMonthStart): ?B2BAmPointSummary
    {
        $previousMonth = $currentMonthStart->copy()->subMonth()->startOfMonth();

        return B2BAmPointSummary::query()
            ->where('user_id', $userId)
            ->whereDate('period_month', $previousMonth->toDateString())
            ->first();
    }

    private function getMonthlyClientData(int $userId, string $startDate, string $endDate): array
    {
        $emails = B2BClient::query()
            ->where('user_id', $userId)
            ->selectRaw('LOWER(TRIM(myads_account)) as email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $topupByEmail = $this->queryTopupByEmails($emails, $startDate, $endDate);

        return [count($emails), $emails, $topupByEmail];
    }

    private function queryTopupByEmails(array $emails, string $startDate, string $endDate): array
    {
        if (empty($emails)) {
            return [];
        }

        return DB::table('report_balance_top_up')
            ->selectRaw('LOWER(TRIM(email_client)) as email, SUM(CAST(total_settlement_klien AS DECIMAL(18,2))) as total_topup')
            ->whereNotNull('total_settlement_klien')
            ->whereBetween('tgl_transaksi', [$startDate, $endDate])
            ->whereIn(DB::raw('LOWER(TRIM(email_client))'), $emails)
            ->groupBy(DB::raw('LOWER(TRIM(email_client))'))
            ->pluck('total_topup', 'email')
            ->map(fn ($value) => (float) $value)
            ->toArray();
    }

    private function queryCampaignPointByEmails(array $emails, string $startDate, string $endDate): int
    {
        return (int) array_sum($this->queryCampaignPointMapByEmails($emails, $startDate, $endDate));
    }

    private function queryCampaignPointMapByEmails(array $emails, string $startDate, string $endDate): array
    {
        static $hasDataPaketTable = null;
        static $hasPanenPoinPackageTable = null;
        static $hasCreatedAtColumn = null;

        if ($hasDataPaketTable === null) {
            $hasDataPaketTable = Schema::hasTable('data_paket_seasonal');
        }
        if ($hasPanenPoinPackageTable === null) {
            $hasPanenPoinPackageTable = Schema::hasTable('panen_poin_package');
        }
        if ($hasCreatedAtColumn === null && $hasDataPaketTable) {
            $hasCreatedAtColumn = Schema::hasColumn('data_paket_seasonal', 'created_at');
        }

        if (empty($emails) || !$hasDataPaketTable) {
            return [];
        }

        $campaignNames = array_map('strtolower', self::CAMPAIGN_PACKAGE_NAMES);

        $query = DB::table('data_paket_seasonal as d')
            ->whereIn(DB::raw('LOWER(TRIM(d.email))'), $emails)
            ->whereIn(DB::raw('LOWER(TRIM(d.name))'), $campaignNames);

        if ($hasCreatedAtColumn) {
            $query->whereBetween('d.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        if ($hasPanenPoinPackageTable) {
            return $query
                ->leftJoin('panen_poin_package as p', DB::raw('LOWER(TRIM(d.name))'), '=', DB::raw('LOWER(TRIM(p.code))'))
                ->selectRaw('LOWER(TRIM(d.email)) as email, SUM(COALESCE(p.point, 0)) as point')
                ->groupBy(DB::raw('LOWER(TRIM(d.email))'))
                ->pluck('point', 'email')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        }

        return $query
            ->selectRaw('LOWER(TRIM(d.email)) as email, COUNT(*) as point')
            ->groupBy(DB::raw('LOWER(TRIM(d.email))'))
            ->pluck('point', 'email')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    private function resolveMonth(?string $month): array
    {
        if ($month) {
            $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        } else {
            $date = now()->startOfMonth();
        }

        [$startDate, $endDate] = $this->monthBoundaries($date);
        $monthValue = $date->format('Y-m');
        $monthLabel = $date->locale('id')->translatedFormat('F Y');

        return [$date, $startDate, $endDate, $monthValue, $monthLabel];
    }

    private function monthBoundaries(Carbon $date): array
    {
        return [
            $date->copy()->startOfMonth()->toDateString(),
            $date->copy()->endOfMonth()->toDateString(),
        ];
    }
}
