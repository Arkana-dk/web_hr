<h2>Slip Gaji: {{ $payroll->employee->name }}</h2>
<p>Periode: {{ $payroll->start_date }} - {{ $payroll->end_date }}</p>

<h3>Pendapatan</h3>
<ul>
@foreach ($payroll->details->where('component_type', 'allowance')->where('effective_month', $payroll_month) as $detail)
    <li>{{ $detail->component_name }}: Rp {{ number_format($detail->amount, 2) }}</li>
@endforeach

@foreach ($payroll->details->where('component_type', 'overtime')->where('effective_month', $payroll_month) as $detail)
    <li>{{ $detail->component_name }}: Rp {{ number_format($detail->amount, 2) }}</li>
@endforeach
</ul>

<h3>Potongan</h3>
<ul>
@foreach ($payroll->details->where('component_type', 'deduction')->where('effective_month', $payroll_month) as $detail)
    <li>{{ $detail->component_name }}: Rp {{ number_format($detail->amount, 2) }}</li>
@endforeach
</ul>

<h3>Total Take Home Pay</h3>
<p>
Rp {{
    number_format(
        $payroll->details->whereIn('component_type', ['allowance', 'overtime'])->where('effective_month', $payroll_month)->sum('amount')
        - $payroll->details->where('component_type', 'deduction')->where('effective_month', $payroll_month)->sum('amount'),
    2) }}
</p>
