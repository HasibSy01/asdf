<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gangguan Sistem - MBG NutriView</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="public-body" style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;">
    <div class="card" style="max-width:520px;width:100%;text-align:center;">
        <img src="{{ asset('images/logo-mbg.png') }}" alt="Logo MBG" style="height:54px;margin:0 auto 14px;display:block;">
        <div class="badge badge-danger" style="margin-bottom:10px;">Kode {{ $status ?? 500 }}</div>
        <h2 style="margin-bottom:8px;">Permintaan Tidak Dapat Diproses</h2>
        <p class="text-muted" style="font-size:13px;line-height:1.6;">
            {{ $message ?? 'Terjadi kendala teknis sementara. Tim operator telah mencatat kejadian ini untuk penanganan lebih lanjut.' }}
        </p>
        <div class="mt-20">
            <a href="{{ url('/') }}" class="btn btn-primary">Kembali ke Portal Publik</a>
        </div>
    </div>
</body>
</html>

