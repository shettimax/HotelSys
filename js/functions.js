// functions.js - Fixed version with modal fixes
(function() {
    var swalConfig = {
        background: '#0f0f0f',
        color: '#00ff99',
        confirmButtonColor: '#00cc66',
        cancelButtonColor: '#ff5555'
    };

    var php56AlertsQueue = [];

    // ===== GLOBAL FUNCTIONS =====
    window.escapeHtml = function(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    window.formatDate = function(dateStr) {
        if (!dateStr) return 'Unknown';
        try {
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return 'Invalid date';
            return d.toLocaleDateString('en-US', {
                month: 'short', 
                day: 'numeric', 
                year: 'numeric'
            });
        } catch (e) {
            console.warn('Date formatting error:', e);
            return 'Invalid date';
        }
    };

    window.formatPrice = function(amount) {
        var num = parseFloat(amount);
        if (isNaN(num)) return '₦0.00';
        return '₦' + num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    window.calculateNights = function(checkin, checkout, pricePerNight, nightsId, totalId) {
        nightsId = nightsId || 'nightsDisplay';
        totalId = totalId || 'totalDisplay';
        
        try {
            var inDate = new Date(checkin);
            var outDate = new Date(checkout);
            
            if (isNaN(inDate.getTime()) || isNaN(outDate.getTime())) {
                throw new Error('Invalid date');
            }
            
            var nights = Math.ceil((outDate - inDate) / (1000 * 60 * 60 * 24));
            
            if (nights < 0) nights = 0;
            
            var nightsElem = document.getElementById(nightsId);
            var totalElem = document.getElementById(totalId);
            
            if (nightsElem) {
                nightsElem.value = nights;
            }
            
            if (totalElem) {
                totalElem.value = formatPrice(nights * pricePerNight);
            }
        } catch (e) {
            console.warn('Night calculation error:', e);
            var nightsElem = document.getElementById(nightsId);
            var totalElem = document.getElementById(totalId);
            
            if (nightsElem) {
                nightsElem.value = '0';
            }
            
            if (totalElem) {
                totalElem.value = formatPrice(0);
            }
        }
    };

    window.validateForm = function(id) {
        var form = document.getElementById(id);
        if (!form) return true;
        
        var fields = form.querySelectorAll('[required]');
        var valid = true;
        
        for (var i = 0; i < fields.length; i++) {
            if (!fields[i].value.trim()) {
                fields[i].style.borderColor = '#ff5555';
                valid = false;
                
                fields[i].classList.add('validation-error');
            } else {
                fields[i].style.borderColor = '';
                fields[i].classList.remove('validation-error');
            }
        }
        
        if (!valid && window.php56Alerts && window.php56Alerts.warning) {
            window.php56Alerts.warning('Please fill all required fields');
        }
        
        return valid;
    };

    // Global modal show helper
    window.showModal = function(modalId) {
        if (typeof $ !== 'undefined') {
            $('#' + modalId).modal('show');
        } else {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                modal.classList.add('show', 'in');
            }
        }
    };

    window.hideModal = function(modalId) {
        if (typeof $ !== 'undefined') {
            $('#' + modalId).modal('hide');
        } else {
            var modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show', 'in');
            }
        }
    };

    window.forceShowModal = function(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'block';
            modal.style.zIndex = '99999';
            modal.style.opacity = '1';
            modal.style.visibility = 'visible';
            modal.classList.add('show', 'in');
        }
    };

    window.filterRooms = function(filter) {
        var cards = document.querySelectorAll('.compact-room-card');
        var buttons = document.querySelectorAll('.filter-btn');
        var visibleCount = 0;
        
        buttons.forEach(function(btn) {
            btn.classList.remove('active');
            if (btn.getAttribute('data-filter') === filter) {
                btn.classList.add('active');
            }
        });
        
        cards.forEach(function(card) {
            var status = card.getAttribute('data-status');
            var shouldShow = false;
            
            switch(filter) {
                case 'all':
                    shouldShow = true;
                    break;
                case 'available':
                    shouldShow = status === 'available';
                    break;
                case 'occupied':
                    shouldShow = status === 'occupied';
                    break;
                case 'reserved':
                    shouldShow = status === 'reserved';
                    break;
                case 'maintenance':
                    shouldShow = status === 'maintenance';
                    break;
                default:
                    shouldShow = true;
            }
            
            if (shouldShow) {
                card.style.display = 'flex';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        var countElement = document.getElementById('roomCount');
        if (countElement) {
            countElement.textContent = visibleCount;
        }
    };

    // Initialize SweetAlert
    if (typeof Swal !== 'undefined') {
        window.php56Swal = Swal;
        window.php56Alerts = {
            success: function(msg) { 
                Swal.fire({
                    title: 'Success',
                    text: msg,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    background: swalConfig.background,
                    color: swalConfig.color,
                    confirmButtonColor: swalConfig.confirmButtonColor
                }); 
            },
            error: function(msg) { 
                Swal.fire({
                    title: 'Error',
                    text: msg,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    background: swalConfig.background,
                    color: '#ff5555',
                    confirmButtonColor: swalConfig.confirmButtonColor
                }); 
            },
            info: function(msg) { 
                Swal.fire({
                    title: 'Info',
                    text: msg,
                    icon: 'info',
                    confirmButtonText: 'OK',
                    background: swalConfig.background,
                    color: '#7d8cff',
                    confirmButtonColor: swalConfig.confirmButtonColor
                }); 
            },
            warning: function(msg) { 
                Swal.fire({
                    title: 'Warning',
                    text: msg,
                    icon: 'warning',
                    confirmButtonText: 'OK',
                    background: swalConfig.background,
                    color: '#ffc107',
                    confirmButtonColor: swalConfig.confirmButtonColor
                }); 
            }
        };
    }

    // ===== NAVBAR DROPDOWN FIXES =====
    function fixNavbarDropdowns() {
        if (typeof $ === 'undefined') {
            console.warn('jQuery not loaded, dropdown fixes disabled');
            return;
        }
        
        // Simple hover support
        $('.dropdown').hover(
            function() {
                $(this).addClass('open');
            },
            function() {
                $(this).removeClass('open');
            }
        );
    }

    // ===== ALERT FUNCTIONS =====
    function processAlertsQueue() {
        for (var i = 0; i < php56AlertsQueue.length; i++) {
            var a = php56AlertsQueue[i];
            if (window.php56Alerts && window.php56Alerts[a.type]) {
                window.php56Alerts[a.type](a.message);
            }
        }
        php56AlertsQueue = [];
    }

    // ===== FORM VALIDATION =====
    function addDeleteConfirms() {
        var buttons = document.querySelectorAll('a[href*="delete="], button[onclick*="delete"]');
        for (var i = 0; i < buttons.length; i++) {
            buttons[i].removeEventListener('click', handleDeleteClick);
            buttons[i].addEventListener('click', handleDeleteClick);
        }
    }

    function handleDeleteClick(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
        return true;
    }

    // ===== PRINT FUNCTIONS =====
    function initPrintButtons() {
        if (typeof window.initPrintButtons !== 'undefined') {
            window.initPrintButtons();
        }
    }

    // ===== INITIALIZATION =====
    function initializeFunctions() {
        processAlertsQueue();
        addDeleteConfirms();
        initPrintButtons();
        
        if (typeof $ !== 'undefined') {
            fixNavbarDropdowns();
            
            // Simple modal handler
            $(document).on('click', '[data-toggle="modal"]', function(e) {
                e.preventDefault();
                var target = $(this).data('target') || $(this).attr('href');
                if (target) {
                    $(target).modal('show');
                }
            });
        }
        
        // Auto-dismiss alerts after 20 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            for (var i = 0; i < alerts.length; i++) {
                if (typeof $ !== 'undefined' && alerts[i].classList.contains('alert-dismissible')) {
                    $(alerts[i]).alert('close');
                }
            }
        }, 20000);
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeFunctions);
    } else {
        initializeFunctions();
    }

    // Also run on jQuery ready
    if (typeof $ !== 'undefined') {
        $(function() {
            fixNavbarDropdowns();
            
            // Simple modal handler for all pages
            $(document).on('click', '[data-toggle="modal"]', function(e) {
                e.preventDefault();
                var target = $(this).data('target') || $(this).attr('href');
                if (target) {
                    $(target).modal('show');
                }
            });
        });
    }

    // Make functions available globally
    window.php56Functions = {
        escapeHtml: window.escapeHtml,
        formatDate: window.formatDate,
        formatPrice: window.formatPrice,
        calculateNights: window.calculateNights,
        validateForm: window.validateForm,
        showModal: window.showModal,
        hideModal: window.hideModal,
        forceShowModal: window.forceShowModal,
        filterRooms: window.filterRooms
    };

    // Debug helper
    if (typeof console !== 'undefined') {
        console.log('PHP56 Functions loaded successfully');
    }
})();