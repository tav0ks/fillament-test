<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('cnpj')->unique();
            $table->string('name');
            $table->string('fantasy_name')->nullable();
            $table->string('phone');
            $table->string('email');
            $table->string('logo')->nullable();
            $table->enum('employee_quantity', [1, 2, 3, 4, 5])->comment('1: 0-10, 2: 11-50, 3: 51-150, 4: 151-300, 5: 300+');
            $table->enum('company_size', [1, 2, 3, 4])->comment('1: Micro, 2: Pequeno, 3: Médio, 4: Grande');
            $table->enum('industry_segment', [1, 2, 3])->comment('1: Indústria, 2: Comércio, 3:Serviço');
            $table->enum('structured_hr_department', [1, 0])->comment('1: SIM, 0: NÃO');
            $table->text('company_profile')->nullable();
            $table->string('responsible_name');
            $table->string('responsible_email');
            $table->string('responsible_phone');
            $table->string('responsible_whatsapp');
            $table->longText('mission')->nullable();
            $table->longText('values')->nullable();
            $table->enum('pdi_program', [1, 0])->nullable()->comment('1: SIM, 0: NÃO');
            $table->string('work_regimes')->nullable();
            $table->string('billing_email');
            $table->string('billing_responsible');
            $table->enum('payment_methods', [1, 2, 3, 4])->comment('1: Boleto, 2: Contrato Faturado, 3: Pix, 4: Cartão');
            $table->date('payment_date')->nullable();
            $table->enum('contract_type', [1, 2])->comment('1: Recorrente, 2: Por uso');
            $table->enum('contract_package', [null, 1, 2])->nullable()->default(null)->comment('1: Recorrente, 2: Por uso');
            $table->integer('billing_address_id')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('clients');
    }
};
