<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payslip {{ $payroll->start_date->format('Y-m') }}</title>
  <style>
    body { font-family: sans-serif; font-size: 12px; }
    .header { text-align: center; margin-bottom: 20px; }
    .section { margin-bottom: 15px; }
    .section h3 { margin-bottom: 5px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table, th, td { border: 1px solid #000; }
    th, td { padding: 6px; text-align: left; }
    .text-right { text-align: right; }
  </style>
</head>
<body>
  <div class="header">
    <h2>Slip Gaji: {{ $payroll->employee->name }}</h2>
    <p>Periode: {{ $payroll->start_date->format('Y-m') }} – {{ $payroll->end_date->format('Y-m') }}</p>
  </div>

  <div class="section">
    <h3>Pendapatan</h3>
    <table>
      <thead>
        <tr>
          <th>Komponen</th>
          <th class="text-right">Jumlah (Rp)</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($payroll->details
            ->whereIn('component_type',['allowance','overtime'])
            ->where('effective_month',$payroll_month)
          as $detail)
          <tr>
            <td>{{ ucfirst($detail->component_type) }} – {{ $detail->component_name }}</td>
            <td class="text-right">{{ number_format($detail->amount, 2, ',', '.') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="section">
    <h3>Potongan</h3>
    <table>
      <thead>
        <tr>
          <th>Komponen</th>
          <th class="text-right">Jumlah (Rp)</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($payroll->details
            ->where('component_type','deduction')
            ->where('effective_month',$payroll_month)
          as $detail)
          <tr>
            <td>{{ $detail->component_name }}</td>
            <td class="text-right">{{ number_format($detail->amount, 2, ',', '.') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="section">
    <h3>Total Take Home Pay</h3>
    <table>
      <tr>
        <th>Total</th>
        <th class="text-right">
          Rp {{
            number_format(
              $payroll->details
                  ->whereIn('component_type',['allowance','overtime'])
                  ->where('effective_month',$payroll_month)
                  ->sum('amount')
              - $payroll->details
                  ->where('component_type','deduction')
                  ->where('effective_month',$payroll_month)
                  ->sum('amount'),
              2, ',', '.'
            )
          }}
        </th>
      </tr>
    </table>
  </div>
</body>
</html>
