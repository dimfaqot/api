<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Nota Pembelian BATEA</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2rem;
            color: #222;
            display: flex;
            justify-content: center;
        }

        .receipt {
            padding: 1rem 2rem;
            width: 360px;
        }

        .header {
            text-align: center;
            margin-bottom: 1rem;
        }

        .header h1 {
            margin: 0;
            font-weight: bold;
            letter-spacing: 1.5px;
        }

        .header p {
            margin: 0;
            line-height: 1.3;
        }

        .line {
            border-top: 1px dashed #888;
            margin: 0.8rem 0;
        }

        .info {
            margin-bottom: 1rem;
            line-height: 1.4;
        }

        .info b {
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 0.2rem 0.3rem;
            text-align: left;
        }

        th {
            font-weight: 700;
            border-bottom: 1px solid #333;
        }

        td.price,
        td.qty,
        td.total {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        tfoot tr:last-child td {
            border-top: none;
        }
    </style>
</head>

<body style="font-size: 10px;">
    <div class="receipt">
        <div class="header">
            <h1><?= profile($decode)['nama']; ?></h1>
            <p>Karangmalang Sragen Jawa Tengah<br /><?= profile($decode)['cp']; ?></p>
        </div>

        <div class="line"></div>

        <div class="info">
            <div><b>Nota:</b> <?= $no_nota; ?></div>
            <div><b>Kasir:</b> <?= $data[0]['petugas']; ?></div>
            <div><b>Tgl:</b> <?= date("d-m-Y H:i:s"); ?></div>
        </div>

        <div class="line"></div>

        <table>
            <thead>
                <tr>
                    <th>Barang</th>
                    <th class="price">Harga</th>
                    <th class="qty">Qty</th>
                    <th class="total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $total = 0;
                $diskon = 0; ?>
                <?php foreach ($data as $i): ?>
                    <?php
                    $total += (int)$i['biaya'];
                    $diskon += (int)$i['diskon'];
                    ?>
                    <tr>
                        <td><?= $i['barang']; ?></td>
                        <td class="price"><?= angka($i['harga']); ?></td>
                        <td class="qty"><?= angka($i['qty']); ?></td>
                        <td class="total"><?= angka($i['biaya']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>

                <tr>
                    <td style="border-top: 1px solid black;" colspan="5"></td>
                </tr>
                <tr>
                    <td colspan="3">Sub Total</td>
                    <td class="total"><?= angka($total); ?></td>
                </tr>
                <tr>
                    <td colspan="3">Diskon</td>
                    <td class="total"><?= angka($diskon); ?></td>
                </tr>
                <tr>
                    <td colspan="3">Total</td>
                    <td class="total"><?= angka($total - $diskon); ?></td>
                </tr>
                <tr>
                    <td colspan="3">Uang</td>
                    <td class="total"><?= angka($uang); ?></td>
                </tr>
                <tr>
                    <td colspan="3">Kembalian</td>
                    <td class="total"><?= angka($uang - ($total - $diskon)); ?></td>
                </tr>
            </tfoot>
        </table>

        <div class="footer" style="text-align: center;margin-top:10px">
            * Terima kasih atas kunjungan anda *
        </div>
    </div>
</body>

</html>