// دالة إغلاق القوائم الجانبية 
window.closeOthers = function(targetId) {
    document.querySelectorAll('.multi-collapse').forEach(el => {
        if (el.id !== targetId && el.classList.contains('show')) {

            if (typeof bootstrap !== 'undefined') {
                new bootstrap.Collapse(el, { toggle: true });
            }
        }
    });
};

// دالة تطبيق الفلاتر (Status, Priority, Complexity)
window.applyFilters = function(type, btnElement) {
    let tableId = 'table-' + type;
    let container = document.querySelector(`#${type}`); 
    if(!container) return;

    // تفعيل الزر المضغوط
    if (btnElement) {
        let parentGroup = btnElement.parentElement;
        parentGroup.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');
    }

    // جلب الفلاتر الحالية
    let statusBtn = container.querySelector('.filter-group-status .filter-btn.active');
    let complexityBtn = container.querySelector('.filter-group-complexity .filter-btn.active');
    let priorityBtn = container.querySelector('.filter-group-priority .filter-btn.active');

    let statusFilter = statusBtn ? statusBtn.getAttribute('data-filter') : 'all';
    let complexityFilter = complexityBtn ? complexityBtn.getAttribute('data-filter') : 'all';
    let priorityFilter = priorityBtn ? priorityBtn.getAttribute('data-filter') : 'all';

    let rows = document.querySelectorAll(`#${tableId} tbody tr.task-row`);
    
    rows.forEach(row => {
        let rowStatus = row.getAttribute('data-status').trim();
        let rowComplexity = row.getAttribute('data-complexity').trim();
        let rowPriority = row.getAttribute('data-priority').trim();
        
        // التحقق من الحالة
        let statusMatch = false;

if (statusFilter === 'all') {
    statusMatch = true;

} else if (statusFilter === 'pending') {
    // جديدة فقط
    if (['pending', 'new'].includes(rowStatus)) {
        statusMatch = true;
    }

} else if (statusFilter === 'approval') {
    // جميع مراحل الاعتماد
    if (['submitted', 'reviewed', 'endorsed', 'waiting_requester'].includes(rowStatus)) {
        statusMatch = true;
    }

} else if (statusFilter === 'submitted') {
    if (rowStatus === 'submitted') {
        statusMatch = true;
    }

} else {
    if (rowStatus === statusFilter) {
        statusMatch = true;
    }
}


        // التحقق من التعقيد والأولوية
        let complexityMatch = (complexityFilter === 'all') || (rowComplexity === complexityFilter);
        let priorityMatch = (priorityFilter === 'all') || (rowPriority === priorityFilter);

        // إظهار أو إخفاء الصف
        if (statusMatch && complexityMatch && priorityMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
};

// دالة البحث (Live Search)
window.setupSearch = function(inputId, tableId) {
    let input = document.getElementById(inputId);
    if(input){
        input.addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll(`#${tableId} tbody tr.task-row`);
            
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
};

// تشغيل البحث عند تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    setupSearch('searchIncoming', 'table-incoming');
    setupSearch('searchOutgoing', 'table-outgoing');
});