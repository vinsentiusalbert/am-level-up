<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAmLevelUp;
use App\Models\User;
use App\Models\Prize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AMLevelUpController extends Controller
{
    // Tampilkan halaman input data
    public function index()
    {
        // logUserLogin();
        return view('amlevelup.inputdatapoin');
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'mobile_phone' => ['required', 'string', 'max:20', 'regex:/^62\\d{9,14}$/'],
            'email' => 'required|email|max:255|unique:leads_master,email',
            'nama' => 'nullable|string|max:255',
            'sector_id' => 'nullable|exists:sectors,id',
            'myads_account' => 'required|string|max:255',
            'remarks' => 'nullable|string|max:1000',
        ], [
            'mobile_phone.regex' => 'Nomor HP harus diawali 62 dan hanya angka (9-14 digit setelah 62).',
            'email.unique' => 'Email sudah terdaftar di leads master, tidak bisa input ulang.',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Auto-create akun di akun_am_level_up
            $emailClient = strtolower(trim($request->myads_account));
            $nomorHp = trim($request->mobile_phone);
            $namaPelanggan = $request->nama ?: $request->company_name;

            // Simpan field input tambahan ke leads_master (bukan user_am_level_up)
            $leadMaster = LeadsMaster::create([
                'user_id' => Auth::id(),
                'source_id' => null,
                'sector_id' => $request->sector_id,
                'company_name' => $request->company_name,
                'mobile_phone' => $nomorHp,
                'email' => strtolower(trim($request->email)),
                'status' => 1,
                'nama' => $request->nama,
                'address' => null,
                'remarks' => $request->remarks,
                'myads_account' => $emailClient,
                'data_type' => 'Eksisting Akun',
            ]);
            
            // Cek apakah akun sudah ada
            $existingAkun = AkunAmLevelUp::where('user_id', Auth::id())
                ->where('email_client', $emailClient)
                ->first();
            
            $isNewAccount = false;
            
            if (!$existingAkun) {
                // Create akun baru
                $akun = AkunAmLevelUp::create([
                    'user_id' => Auth::id(),
                    'leads_master_id' => $leadMaster->id,
                    'nama_akun' => $namaPelanggan,
                    'email_client' => $emailClient,
                    'password' => bcrypt('123456'), // Default password
                    'source' => 'user_am_level_up',
                ]);
                
                // \Log::info("Akun created for: {$emailClient}");
                $isNewAccount = true;
                
                // Simpan ke user_am_level_up hanya jika akun baru
                $amlevelup = UserAmLevelUp::create([
                    'user_id' => Auth::id(),
                    'nama_pelanggan' => $namaPelanggan,
                    'akun_myads_pelanggan' => $emailClient,
                    'nomor_hp_pelanggan' => $nomorHp,
                ]);
            } else {
                $akun = $existingAkun;
                if (!$akun->leads_master_id || $akun->leads_master_id !== $leadMaster->id) {
                    $akun->update(['leads_master_id' => $leadMaster->id]);
                }
                \Log::info("Akun already exists for: {$emailClient}");
                $isNewAccount = false;
            }
            
            // Kirim notifikasi email & WhatsApp (untuk akun baru atau existing)
            try {
                $this->sendAccountNotification(
                    $akun,
                    $nomorHp,
                    '123456' // Plain password untuk notifikasi
                );
            } catch (\Exception $e) {
                \Log::warning("Notification failed (not blocking transaction): " . $e->getMessage());
                // Jangan block transaksi - akun sudah dibuat, notifikasi bisa dicoba ulang
            }
            
            DB::commit();
            
            // Refresh summary AM Level UP untuk user ini (langsung setelah input)
            try {
                $this->refreshSummaryForSingleUser(Auth::id(), $emailClient);
                \Log::info("Summary refreshed immediately after input for email: {$emailClient}");
            } catch (\Exception $e) {
                \Log::warning("Failed to refresh summary immediately: " . $e->getMessage());
                // Tidak masalah, akan diupdate scheduler jam 7 pagi
            }
            
            // Tentukan pesan berdasarkan apakah akun baru atau existing
            if ($isNewAccount) {
                $successMessage = 'Data pelanggan berhasil disimpan dan akun telah dibuat!';
            } else {
                $successMessage = 'Akun sudah pernah dibuat, notifikasi telah dikirimkan ulang!';
            }
            
            return redirect()->route('amlevelup.index')
                ->with('success', $successMessage)
                ->with('is_existing_account', !$isNewAccount);
                
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error in store: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    // Tampilkan halaman report
    public function report()
    {
        logUserLogin();
        $months = [];

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->format('Y-m-01'); // bulan sekarang, tanggal 01

        for ($i = 1; $i <= 12; ++$i) {
            $date = Carbon::create($currentYear, $i, 1);
            $months[] = [
                'value' => $date->format('Y-m-d'), // e.g., 2025-05-01
                'label' => $date->translatedFormat('F Y'), // e.g., Mei 2025
                'selected' => $date->format('Y-m-d') === $currentMonth,
            ];
        }
        return view('amlevelup.reportpoin', compact('months'));
    }
    
    // Get data untuk DataTable
    public function getReportData(Request $request)
    {  
        \Log::info('=== GET REPORT DATA CALLED ===');
        // \Log::info('User: ' . Auth::user()->name);
        \Log::info('Request URI: ' . $request->getRequestUri());
        \Log::info('Filter Tanggal: ' . $request->tanggal);
        
        try {
            $user = auth()->user();
            \Log::info('Starting calculateAmLevelUpData...');
            $data = $this->calculateAmLevelUpData($request->tanggal);
            $prizes = Prize::orderBy('point', 'desc')->get();
            $headerSummary = null;
            if ($user) {
                $date = Carbon::today();
                $userEmail = $user->email_client ?? $user->email;
                if (($user->role ?? null) === 'b2b') {
                    $summary = DB::table('b2b_am_point_summaries')
                        ->where('user_id', $user->id)
                        ->whereDate('period_month', $date->copy()->startOfMonth()->toDateString())
                        ->first();
                    $previousSummary = DB::table('b2b_am_point_summaries')
                        ->where('user_id', $user->id)
                        ->whereDate('period_month', $date->copy()->subMonth()->startOfMonth()->toDateString())
                        ->first();

                    $finalPoint = 0;
                    if ($summary) {
                        $basePoint = ((int) ($summary->point_rounded ?? 0)) + ((int) ($summary->campaign_point ?? 0));
                        $finalPoint = max($basePoint - ((int) ($summary->total_redeem_point ?? 0)), 0);
                    }

                    $headerSummary = [
                        'bulan_ini' => [
                            'label' => $date->copy()->translatedFormat('F Y'),
                            'total_topup' => (float) ($summary->total_topup ?? 0),
                            'total_poin_bruto' => ((int) ($summary->point_rounded ?? 0)) + ((int) ($summary->campaign_point ?? 0)),
                        ],
                        'bulan_lalu' => [
                            'label' => $date->copy()->subMonth()->translatedFormat('F Y'),
                            'total_topup' => (float) ($previousSummary->total_topup ?? 0),
                            'total_poin' => max((((int) ($previousSummary->point_rounded ?? 0)) + ((int) ($previousSummary->campaign_point ?? 0))) - ((int) ($previousSummary->total_redeem_point ?? 0)), 0),
                        ],
                        'carry_in_point' => ((int) ($previousSummary->point_rounded ?? 0)) + ((int) ($previousSummary->campaign_point ?? 0)),
                        'redeem' => (int) ($summary->total_redeem_point ?? 0),
                        'poin_akhir' => (int) $finalPoint,
                    ];

                    $point = (object) [
                        'poin' => (int) $finalPoint,
                    ];
                } else {
                    $point = DB::table('summary_am_level_up')
                        ->select(
                            'nama_canvasser',
                            'email_client',
                            'nomor_hp_client',
                            DB::raw('CAST(total_settlement AS DECIMAL(15,2)) as total_settlement_raw'),
                            DB::raw('FORMAT(total_settlement, 0, "id_ID") as total_settlement'),
                            'poin_bulan_ini',
                            'poin_akumulasi',
                            DB::raw('(poin + poin_package) as poin'),
                            'bulan'
                        )->where('email_client', '=', $userEmail)
                            ->whereMonth('created_at', $date->month)
                            ->whereYear('created_at', $date->year)->first();
                }
            } else {
                $point = 0;
            }
            $redeem = DB::table('redeem_prizes_am_level_up')->where('user_id', auth()->id())->first();

            $redeemedPrizeId = $redeem?->prize_id; // null kalau belum redeem
            $hasRedeemed = (bool) $redeem;
            $today = Carbon::today();

            // Set tanggal mulai redeem (1 Maret tahun ini)
            $redeemStartDate = Carbon::create($today->year, 3, 1);

            // true kalau sudah boleh redeem
            $isRedeemPeriod = $today->gte($redeemStartDate);
            return view('amlevelup.index', compact('data', 'point','prizes', 'hasRedeemed', 'redeemedPrizeId',
    'isRedeemPeriod', 'headerSummary'));
                
        } catch (\Exception $e) {
            \Log::error("Error in getReportData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    // Hitung data AM Level UP (ambil dari summary table)
    private function calculateAmLevelUpData($tanggal = null)
    {
        try {
            $today = Carbon::today();
            $periodMonth = $today->copy()->startOfMonth()->toDateString();

            // Prioritas: samakan data Top 10 homepage dengan leaderboard login (B2B summary).
            $b2bRows = DB::table('b2b_am_point_summaries as s')
                ->join('users as u', 'u.id', '=', 's.user_id')
                ->whereDate('s.period_month', $periodMonth)
                ->where('u.role', 'b2b')
                ->select(
                    'u.name as nama_canvasser',
                    'u.name as nama_akun',
                    'u.email as email_client',
                    DB::raw('GREATEST(((COALESCE(s.point_rounded,0) + COALESCE(s.campaign_point,0)) - COALESCE(s.total_redeem_point,0)), 0) as poin')
                )
                ->orderByDesc(DB::raw('GREATEST(((COALESCE(s.point_rounded,0) + COALESCE(s.campaign_point,0)) - COALESCE(s.total_redeem_point,0)), 0)'))
                ->get();

            if ($b2bRows->isNotEmpty()) {
                $mapResult = function ($rows) use ($today) {
                    return $rows->take(10)->map(function ($item) use ($today) {
                        return [
                            'nama_canvasser' => $item->nama_canvasser,
                            'email_client' => $item->email_client,
                            'nomor_hp_client' => '-',
                            'total_settlement' => '0',
                            'total_settlement_raw' => 0,
                            'poin_bulan_ini' => 0,
                            'poin_akumulasi' => 0,
                            'poin' => (int) $item->poin,
                            'bulan' => $today->locale('id')->translatedFormat('F Y'),
                            'uuid' => null,
                            'nama_akun' => $item->nama_akun,
                        ];
                    })->toArray();
                };

                return [
                    'poin_0_100' => $mapResult($b2bRows->filter(fn ($r) => (int) $r->poin >= 0 && (int) $r->poin <= 100)->values()),
                    'poin_101_200' => $mapResult($b2bRows->filter(fn ($r) => (int) $r->poin >= 101 && (int) $r->poin <= 200)->values()),
                    'poin_201_300' => $mapResult($b2bRows->filter(fn ($r) => (int) $r->poin >= 201)->values()),
                ];
            }

            \Log::info("=== READING FROM SUMMARY TABLE ===");

            $baseQuery = DB::table('summary_am_level_up as s')
                ->join('akun_am_level_up as u', 'u.email_client', '=', 's.email_client')
                ->leftJoin('mitra_sbp', 's.email_client', '=', 'mitra_sbp.email_myads')
                // Exclude email yang ada di mitra_sbp
                ->whereNull('mitra_sbp.id')
                ->select(
                    's.nama_canvasser',
                    's.email_client',
                    's.nomor_hp_client',
                    DB::raw('CAST(s.total_settlement AS DECIMAL(15,2)) as total_settlement_raw'),
                    's.poin_bulan_ini',
                    's.poin_akumulasi',
                    's.poin',
                    's.poin_package',
                    DB::raw('(s.poin + s.poin_package) as total_poin'),
                    's.bulan',
                    'u.uuid',
                    'u.nama_akun',
                    // 'u.akun_myads_pelanggan',
                    // 'u.nomor_hp_pelanggan',
                    // 'u.nama_pelanggan'
                );

            // Filter bulan
            // if ($tanggal) {
                $date = Carbon::today();
                $baseQuery->whereMonth('s.created_at', $date->month)
                        ->whereYear('s.created_at', $date->year);
            // }
            // Helper mapper
            $mapResult = function ($query) {
                return $query->orderBy(DB::raw('(s.poin + s.poin_package)'), 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($item) {
                        return [
                            'nama_canvasser' => $item->nama_canvasser,
                            'email_client' => $item->email_client,
                            'nomor_hp_client' => $item->nomor_hp_client,
                            'total_settlement' => number_format($item->total_settlement_raw, 0, ',', '.'),
                            'total_settlement_raw' => $item->total_settlement_raw,
                            'poin_bulan_ini' => $item->poin_bulan_ini,
                            'poin_akumulasi' => $item->poin_akumulasi,
                            'poin' => $item->total_poin,
                            'bulan' => $item->bulan,
                            'uuid' => $item->uuid,
                            'nama_akun' => $item->nama_akun,
                            // 'akun_myads_pelanggan' => $item->akun_myads_pelanggan,
                            // 'nomor_hp_pelanggan' => $item->nomor_hp_pelanggan,
                            // 'nama_pelanggan' => $item->nama_pelanggan,
                        ];
                    })
                    ->toArray();
            };

            $result = [
                'poin_0_100' => $mapResult(
                    (clone $baseQuery)->whereBetween(DB::raw('(s.poin + s.poin_package)'), [0, 100])
                ),
                'poin_101_200' => $mapResult(
                    (clone $baseQuery)->whereBetween(DB::raw('(s.poin + s.poin_package)'), [101, 200])
                ),
                'poin_201_300' => $mapResult(
                    (clone $baseQuery)->whereBetween(DB::raw('(s.poin + s.poin_package)'), [201, 1000])
                ),
            ];

            \Log::info("Top 10 results generated per poin range");

            return $result;

        } catch (\Exception $e) {
            \Log::error("Error in calculateAmLevelUpData: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return [];
        }
    }

    
    // Export ke Excel
    public function export(Request $request)
    {
        try {
            $data = $this->calculateAmLevelUpData($request->tanggal);
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Header
            $monthYear = Carbon::now()->locale('id')->translatedFormat('F Y');
            $sheet->setCellValue('A1', 'LAPORAN AM LEVEL UP - ' . strtoupper($monthYear));
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            
            // Column headers
            $sheet->setCellValue('A3', 'No');
            $sheet->setCellValue('B3', 'Nama Canvasser');
            $sheet->setCellValue('C3', 'Email Client');
            $sheet->setCellValue('D3', 'Nomor HP Client');
            $sheet->setCellValue('E3', 'Total Settlement');
            $sheet->setCellValue('F3', 'Poin');
            
            $sheet->getStyle('A3:F3')->getFont()->setBold(true);
            $sheet->getStyle('A3:F3')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD9D9D9');
            
            // Data
            $row = 4;
            $no = 1;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $no++);
                $sheet->setCellValue('B' . $row, $item['nama_canvasser']);
                $sheet->setCellValue('C' . $row, $item['email_client']);
                $sheet->setCellValue('D' . $row, $item['nomor_hp_client']);
                $sheet->setCellValue('E' . $row, $item['total_settlement']);
                $sheet->setCellValue('F' . $row, $item['poin']);
                $row++;
            }
            
            // Auto width
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Download
            $fileName = 'Laporan_AM_Level_UP_' . $monthYear . '.xlsx';
            $writer = new Xlsx($spreadsheet);
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $fileName . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
            
        } catch (\Exception $e) {
            \Log::error("Error in export: " . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data: ' . $e->getMessage());
        }
    }
    
    // Refresh Summary AM Level UP (untuk di-schedule)
    public function refreshSummaryAmLevelUp()
    {
        try {
            \Log::info('=== REFRESH SUMMARY AM LEVEL UP STARTED ===');
            
            // Tentukan range tanggal bulan berjalan
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
            
            // Ambil semua canvasser
            $canvassers = User::where('role', 'cvsr')->get();
            
            $totalProcessed = 0;
            
            // Hapus data summary bulan ini dulu
            DB::table('summary_am_level_up')->truncate();
            
            foreach ($canvassers as $canvasser) {
                // Ambil email dari user_am_level_up yang diinput oleh canvasser ini
                $panenPoinData = UserAmLevelUp::where('user_id', $canvasser->id)
                    ->select('akun_myads_pelanggan', 'nomor_hp_pelanggan')
                    ->get();
                
                $clientEmails = [];
                
                if ($panenPoinData->isNotEmpty()) {
                    foreach ($panenPoinData as $data) {
                        $clientEmails[] = [
                            'email' => strtolower(trim($data->akun_myads_pelanggan)),
                            'nomor_hp' => $data->nomor_hp_pelanggan
                        ];
                    }
                } else {
                    $leadsData = DB::table('leads_master')
                        ->where('user_id', $canvasser->id)
                        ->select('email', 'mobile_phone')
                        ->get();
                    
                    foreach ($leadsData as $lead) {
                        $clientEmails[] = [
                            'email' => strtolower(trim($lead->email)),
                            'nomor_hp' => $lead->mobile_phone ?? '-'
                        ];
                    }
                }
                
                if (empty($clientEmails)) {
                    continue;
                }
                
                $emails = array_column($clientEmails, 'email');
                
                // Query settlement bulan ini
                $settlementsThisMonth = DB::table('report_balance_top_up')
                    ->select(DB::raw('LOWER(TRIM(email_client)) as email'), DB::raw('SUM(CAST(total_settlement_klien AS DECIMAL(15,2))) as total'))
                    ->whereBetween('tgl_transaksi', [$startDate, $endDate])
                    ->whereNotNull('total_settlement_klien')
                    ->whereIn(DB::raw('LOWER(TRIM(email_client))'), $emails)
                    ->groupBy(DB::raw('LOWER(TRIM(email_client))'))
                    ->pluck('total', 'email')
                    ->toArray();
                
                // Query settlement akumulasi
                $settlementsAccumulated = [];
                $currentMonth = Carbon::now()->month;
                if ($currentMonth > 1) {
                    $startYearDate = Carbon::now()->startOfYear()->format('Y-m-d');
                    $endPreviousMonth = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                    
                    $settlementsAccumulated = DB::table('report_balance_top_up')
                        ->select(DB::raw('LOWER(TRIM(email_client)) as email'), DB::raw('SUM(CAST(total_settlement_klien AS DECIMAL(15,2))) as total'))
                        ->whereBetween('tgl_transaksi', [$startYearDate, $endPreviousMonth])
                        ->whereNotNull('total_settlement_klien')
                        ->whereIn(DB::raw('LOWER(TRIM(email_client))'), $emails)
                        ->groupBy(DB::raw('LOWER(TRIM(email_client))'))
                        ->pluck('total', 'email')
                        ->toArray();
                }
                
                // Insert ke summary table
                foreach ($clientEmails as $client) {
                    $email = $client['email'];
                    $totalSettlement = $settlementsThisMonth[$email] ?? 0;
                    $settlementPrevious = $settlementsAccumulated[$email] ?? 0;
                    
                    if ($totalSettlement == 0 && $settlementPrevious == 0) {
                        continue;
                    }
                    
                    $poinBulanIni = floor($totalSettlement / 250000);
                    $poinAkumulasi = floor($settlementPrevious / 250000);
                    $totalPoin = $poinBulanIni + $poinAkumulasi;
                    
                    DB::table('summary_am_level_up')->insert([
                        'user_id' => $canvasser->id,
                        'nama_canvasser' => $canvasser->name,
                        'email_client' => $email,
                        'nomor_hp_client' => $client['nomor_hp'],
                        'total_settlement' => $totalSettlement,
                        'poin_bulan_ini' => $poinBulanIni,
                        'poin_akumulasi' => $poinAkumulasi,
                        'poin' => $totalPoin,
                        'bulan' => Carbon::now()->locale('id')->translatedFormat('F Y'),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    $totalProcessed++;
                }
            }
            
            \Log::info("Summary AM Level UP refreshed. Total records: {$totalProcessed}");
            
            return response()->json([
                'status' => 'success',
                'message' => "Summary AM Level UP updated. Total records: {$totalProcessed}"
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error in refreshSummaryAmLevelUp: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function redeemPrize(Request $request)
    {
        $request->validate([
            'prize_id' => 'required|integer|exists:prizes_am_level_up,id',
        ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Silakan login terlebih dahulu'
            ], 401);
        }
        $today = Carbon::today();
        $redeemStartDate = Carbon::create($today->year, 3, 1);

        if ($today->lt($redeemStartDate)) {
            return response()->json([
                'status' => false,
                'message' => 'Redeem hanya bisa dilakukan mulai 1 Maret'
            ]);
        }
        try {
            DB::transaction(function () use ($request, $user) {

                // Lock hadiah
                $prize = Prize::where('id', $request->prize_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($prize->stock <= 0) {
                    throw new \Exception('Stok hadiah habis');
                }

                // Lock redeem user
                $alreadyRedeem = DB::table('redeem_prizes_am_level_up')
                    ->where('user_id', $user->id)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyRedeem) {
                    throw new \Exception('Anda sudah pernah redeem hadiah');
                }

                // Lock poin user
                $date = now();
                $userEmail = $user->email_client ?? $user->email;
                $requiredPoint = (int) $prize->point;

                if (($user->role ?? null) === 'b2b') {
                    $periodMonth = $date->copy()->startOfMonth()->toDateString();

                    $userPointRecord = DB::table('b2b_am_point_summaries')
                        ->where('user_id', $user->id)
                        ->whereDate('period_month', $periodMonth)
                        ->lockForUpdate()
                        ->first();

                    $userPoint = ((int) ($userPointRecord->point_rounded ?? 0)) + ((int) ($userPointRecord->campaign_point ?? 0));
                    $userPointRedeem = (int) ($userPointRecord->total_redeem_point ?? 0);
                    $availablePoint = max($userPoint - $userPointRedeem, 0);
                } else {
                    $userPointRecord = DB::table('summary_am_level_up')
                        ->where('email_client', $userEmail)
                        ->whereMonth('created_at', $date->month)
                        ->whereYear('created_at', $date->year)
                        ->lockForUpdate()
                        ->first();

                    $userPoint = (int) ($userPointRecord->poin ?? 0);
                    $userPointPackage = (int) ($userPointRecord->poin_package ?? 0);
                    $availablePoint = $userPoint + $userPointPackage;
                }

                if ($availablePoint < $requiredPoint) {
                    throw new \Exception('Poin tidak cukup untuk menukar hadiah ini');
                }

                // Kurangi stok
                $prize->decrement('stock');

                // Simpan redeem
                DB::table('redeem_prizes_am_level_up')->insert([
                    'user_id' => $user->id,
                    'prize_id' => $prize->id,
                    'point_used' => $requiredPoint,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update summary setelah redeem
                if (($user->role ?? null) === 'b2b') {
                    $periodMonth = $date->copy()->startOfMonth()->toDateString();
                    $totalRedeemPoint = (int) DB::table('redeem_prizes_am_level_up')
                        ->where('user_id', $user->id)
                        ->sum('point_used');

                    DB::table('b2b_am_point_summaries')
                        ->where('user_id', $user->id)
                        ->whereDate('period_month', $periodMonth)
                        ->update([
                            'total_redeem_point' => $totalRedeemPoint,
                            'updated_at' => now(),
                        ]);
                } else {
                    $this->updateSummaryAfterRedeem($user->id);
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'Hadiah berhasil ditukar'
            ]);

        } catch (\Exception $e) {
            \Log::error('Redeem Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }



    // Update summary setelah redeem (dipanggil dari RedeemController)
    public function updateSummaryAfterRedeem($userId)
    {
        try {
            \Log::info("=== UPDATE SUMMARY AFTER REDEEM FOR USER: {$userId} ===");
            
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;
            
            // Hitung total poin yang sudah di-redeem user ini bulan ini
            $totalPoinRedeem = DB::table('redeem_prizes_am_level_up')
                ->where('user_id', $userId)
                ->whereMonth('created_at', $currentMonth)
                ->whereYear('created_at', $currentYear)
                ->sum('point_used') ?? 0;

            $akun = DB::table('akun_am_level_up')
                ->where('id', $userId)->first();
                
            \Log::info("Total poin redeem for user {$userId}: {$totalPoinRedeem}");
            
            // Update semua record summary user ini di bulan ini
            $latestSummary = DB::table('summary_am_level_up')
                ->where('email_client', $akun->email_client)
                ->latest('created_at')
                ->first();

            $updatedCount = 0;

            if ($latestSummary) {
                $poinSisa = $latestSummary->poin - $totalPoinRedeem;
                $remark = $this->calculateRemark($poinSisa);

                DB::table('summary_am_level_up')
                    ->where('id', $latestSummary->id)
                    ->update([
                        'poin_redeem' => $totalPoinRedeem,
                        'poin' => $poinSisa,
                        'remark' => $remark,
                        'updated_at' => now()
                    ]);

                $updatedCount = 1;
            }

            
            \Log::info("Updated {$updatedCount} summary records after redeem");
            
            return [
                'success' => true,
                'updated' => $updatedCount,
                'total_redeem' => $totalPoinRedeem
            ];
            
        } catch (\Exception $e) {
            \Log::error("Error in updateSummaryAfterRedeem: " . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    // Hitung remark berdasarkan poin sisa
    private function calculateRemark($poinSisa)
    {
        if ($poinSisa >= 0 && $poinSisa <= 100) {
            return 'Rookie';
        } elseif ($poinSisa >= 101 && $poinSisa <= 200) {
            return 'Rising Star';
        } elseif ($poinSisa >= 201) {
            return 'Champion';
        }
        return 'Rookie'; // default
    }
}
