<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_items', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_items', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('item');
            }

            if (! Schema::hasColumn('sales_items', 'service_id')) {
                $table->unsignedBigInteger('service_id')->nullable()->after('category_id');
            }

            if (! Schema::hasColumn('sales_items', 'discount')) {
                $table->double('discount')->default(0)->after('rate');
            }

            if (! Schema::hasColumn('sales_items', 'taxable')) {
                $table->double('taxable')->default(0)->after('discount');
            }

            if (! Schema::hasColumn('sales_items', 'tax')) {
                $table->double('tax')->default(0)->after('taxable');
            }

            if (! Schema::hasColumn('sales_items', 'product_unit_id')) {
                $table->unsignedBigInteger('product_unit_id')->nullable()->after('owner_type');
            }

            if (! Schema::hasColumn('sales_items', 'headline')) {
                $table->string('headline')->nullable()->after('description');
            }

            if (! Schema::hasColumn('sales_items', 'subheadline')) {
                $table->string('subheadline')->nullable()->after('headline');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_items', function (Blueprint $table) {
            foreach ([
                'category_id',
                'service_id',
                'discount',
                'taxable',
                'tax',
                'product_unit_id',
                'headline',
                'subheadline',
            ] as $column) {
                if (Schema::hasColumn('sales_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
