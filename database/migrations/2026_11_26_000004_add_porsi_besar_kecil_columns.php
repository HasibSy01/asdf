<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelompok_penerimas', function (Blueprint $table) {
            $table->integer('penerima_porsi_besar')->default(0)->after('jumlah_penerima');
            $table->integer('penerima_porsi_kecil')->default(0)->after('penerima_porsi_besar');
            $table->integer('terkemas_porsi_besar')->default(0)->after('porsi_terkemas');
            $table->integer('terkemas_porsi_kecil')->default(0)->after('terkemas_porsi_besar');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->integer('ukuran_porsi_besar_gram')->default(450)->after('ukuran_porsi_gram');
            $table->integer('ukuran_porsi_kecil_gram')->default(300)->after('ukuran_porsi_besar_gram');
            $table->integer('porsi_besar_rencana')->default(200)->after('jumlah_porsi_rencana');
            $table->integer('porsi_kecil_rencana')->default(100)->after('porsi_besar_rencana');
        });

        Schema::table('menu_bahans', function (Blueprint $table) {
            $table->decimal('berat_porsi_besar_gram', 8, 2)->default(0)->after('berat_per_sajian_gram');
            $table->decimal('berat_porsi_kecil_gram', 8, 2)->default(0)->after('berat_porsi_besar_gram');
        });

        Schema::table('pengantaran_titiks', function (Blueprint $table) {
            $table->integer('porsi_besar_dikirim')->default(0)->after('porsi_dikirim');
            $table->integer('porsi_kecil_dikirim')->default(0)->after('porsi_besar_dikirim');
        });

        // Sinkronisasi data lama yang sudah ada di database agar langsung terisi proporsional
        foreach (DB::table('kelompok_penerimas')->get() as $kp) {
            $totalP = (int) $kp->jumlah_penerima;
            $kecilP = (int) round($totalP * 0.35);
            $besarP = max(0, $totalP - $kecilP);

            $totalK = (int) $kp->porsi_terkemas;
            $kecilK = min($totalK, $kecilP);
            $besarK = max(0, $totalK - $kecilK);

            DB::table('kelompok_penerimas')->where('id', $kp->id)->update([
                'penerima_porsi_besar' => $besarP,
                'penerima_porsi_kecil' => $kecilP,
                'terkemas_porsi_besar' => $besarK,
                'terkemas_porsi_kecil' => $kecilK,
            ]);
        }

        foreach (DB::table('menus')->get() as $m) {
            $totalRencana = (int) $m->jumlah_porsi_rencana;
            $kecilR = (int) round($totalRencana * 0.35);
            $besarR = max(0, $totalRencana - $kecilR);
            $gramBesar = (int) ($m->ukuran_porsi_gram ?: 450);
            $gramKecil = max(150, (int) round($gramBesar * 0.68));

            DB::table('menus')->where('id', $m->id)->update([
                'ukuran_porsi_besar_gram' => $gramBesar,
                'ukuran_porsi_kecil_gram' => $gramKecil,
                'porsi_besar_rencana' => $besarR,
                'porsi_kecil_rencana' => $kecilR,
            ]);
        }

        foreach (DB::table('menu_bahans')->get() as $mb) {
            $gBesar = (float) $mb->berat_per_sajian_gram;
            $gKecil = round($gBesar * 0.68, 1);
            DB::table('menu_bahans')->where('id', $mb->id)->update([
                'berat_porsi_besar_gram' => $gBesar,
                'berat_porsi_kecil_gram' => $gKecil,
            ]);
        }

        foreach (DB::table('pengantaran_titiks')->get() as $pt) {
            $tot = (int) $pt->porsi_dikirim;
            $kecil = (int) round($tot * 0.35);
            $besar = max(0, $tot - $kecil);
            DB::table('pengantaran_titiks')->where('id', $pt->id)->update([
                'porsi_besar_dikirim' => $besar,
                'porsi_kecil_dikirim' => $kecil,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('pengantaran_titiks', function (Blueprint $table) {
            $table->dropColumn(['porsi_besar_dikirim', 'porsi_kecil_dikirim']);
        });

        Schema::table('menu_bahans', function (Blueprint $table) {
            $table->dropColumn(['berat_porsi_besar_gram', 'berat_porsi_kecil_gram']);
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn([
                'ukuran_porsi_besar_gram',
                'ukuran_porsi_kecil_gram',
                'porsi_besar_rencana',
                'porsi_kecil_rencana',
            ]);
        });

        Schema::table('kelompok_penerimas', function (Blueprint $table) {
            $table->dropColumn([
                'penerima_porsi_besar',
                'penerima_porsi_kecil',
                'terkemas_porsi_besar',
                'terkemas_porsi_kecil',
            ]);
        });
    }
};

