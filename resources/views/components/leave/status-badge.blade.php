@php
$map = [
  'pending'  => ['text'=>'Pending','class'=>'badge bg-warning-subtle text-warning border'],
  'approved' => ['text'=>'Approved','class'=>'badge bg-success-subtle text-success border'],
  'rejected' => ['text'=>'Rejected','class'=>'badge bg-danger-subtle text-danger border'],
  'cancelled'=> ['text'=>'Cancelled','class'=>'badge bg-secondary-subtle text-secondary border'],
];
$st = strtolower($status ?? '');
$cfg = $map[$st] ?? ['text'=>ucfirst($st ?: 'Unknown'),'class'=>'badge bg-light text-muted border'];
@endphp
<span class="{{ $cfg['class'] }}">{{ $cfg['text'] }}</span>
