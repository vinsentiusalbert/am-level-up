<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('b2b_clients')) {
            return;
        }

        Schema::table('b2b_clients', function (Blueprint $table) {
            if (!Schema::hasColumn('b2b_clients', 'company_name')) {
                $table->string('company_name')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('b2b_clients', 'customer_phone')) {
                $table->string('customer_phone', 30)->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('b2b_clients', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('customer_phone');
            }
            if (!Schema::hasColumn('b2b_clients', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('customer_email');
            }
            if (!Schema::hasColumn('b2b_clients', 'sector')) {
                $table->string('sector')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('b2b_clients', 'myads_account')) {
                $table->string('myads_account')->nullable()->after('sector');
            }
            if (!Schema::hasColumn('b2b_clients', 'remarks')) {
                $table->text('remarks')->nullable()->after('myads_account');
            }
        });

        // Backfill dari struktur lama (jika sebelumnya ada kolom lama)
        if (Schema::hasColumn('b2b_clients', 'client_phone')) {
            DB::statement("UPDATE b2b_clients SET customer_phone = COALESCE(customer_phone, client_phone)");
        }
        if (Schema::hasColumn('b2b_clients', 'client_email')) {
            DB::statement("UPDATE b2b_clients SET customer_email = COALESCE(customer_email, client_email)");
            DB::statement("UPDATE b2b_clients SET myads_account = COALESCE(myads_account, client_email)");
        }
        if (Schema::hasColumn('b2b_clients', 'client_name')) {
            DB::statement("UPDATE b2b_clients SET customer_name = COALESCE(customer_name, client_name)");
        }

        // Tambah unique index untuk kombinasi user + akun myads jika belum ada.
        // Gunakan try/catch karena bisa gagal jika data existing duplikat.
        try {
            DB::statement('CREATE UNIQUE INDEX b2b_clients_user_id_myads_account_unique ON b2b_clients (user_id, myads_account)');
        } catch (\Throwable $e) {
            // Skip jika index sudah ada / data duplikat.
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('b2b_clients')) {
            return;
        }

        try {
            DB::statement('DROP INDEX b2b_clients_user_id_myads_account_unique ON b2b_clients');
        } catch (\Throwable $e) {
            // Skip
        }
    }
};

