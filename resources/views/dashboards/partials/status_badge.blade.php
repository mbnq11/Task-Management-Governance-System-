@if($status == 'completed')
<span class="badge bg-success">مكتملة</span>

@elseif($status == 'submitted')
<span class="badge bg-info text-dark">بانتظار الاعتماد</span>

@elseif($status == 'returned')
<span class="badge bg-danger">معادة للتعديل</span>

@elseif($status == 'in_progress')
<span class="badge bg-primary">جاري التنفيذ</span>

@elseif($status == 'closed')
<span class="badge bg-dark">مغلقة</span>

@elseif($status == 'archived')
<span class="badge bg-purple">مؤرشفة</span>

@else
<span class="badge bg-secondary">جديدة</span>
@endif