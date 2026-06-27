<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            if (!Schema::hasColumn('invoices', 'branch_id')) {
                $table->unsignedInteger('branch_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('invoices', 'project_id')) {
                $table->unsignedInteger('project_id')->nullable()->after('customer_id');
            }

            if (!Schema::hasColumn('invoices', 'vendor_code')) {
                $table->string('vendor_code')->nullable()->after('project_id');
            }

            if (!Schema::hasColumn('invoices', 'invoice_month')) {
                $table->string('invoice_month')->nullable()->after('invoice_date');
            }

            if (!Schema::hasColumn('invoices', 'user_id')) {
                $table->unsignedInteger('user_id')->nullable()->after('sales_agent_id');
            }

            if (!Schema::hasColumn('invoices', 'percentage_discount')) {
                $table->double('percentage_discount')->nullable()->after('discount');
            }

            if (!Schema::hasColumn('invoices', 'absent_deduction')) {
                $table->double('absent_deduction')->nullable()->default(0)->after('percentage_discount');
            }

            if (!Schema::hasColumn('invoices', 'allowance_deduction')) {
                $table->double('allowance_deduction')->nullable()->default(0)->after('absent_deduction');
            }

            if (!Schema::hasColumn('invoices', 'damage_deduction')) {
                $table->double('damage_deduction')->nullable()->default(0)->after('allowance_deduction');
            }

            if (!Schema::hasColumn('invoices', 'hsn_tax')) {
                $table->string('hsn_tax')->nullable()->after('damage_deduction');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $columns = [
                'branch_id',
                'project_id',
                'vendor_code',
                'invoice_month',
                'user_id',
                'percentage_discount',
                'absent_deduction',
                'allowance_deduction',
                'damage_deduction',
                'hsn_tax',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
