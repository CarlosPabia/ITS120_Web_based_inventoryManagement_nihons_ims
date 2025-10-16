const setActiveSidebarLink = () => {
    const currentPath = window.location.pathname;
    const currentPageFile = currentPath.substring(currentPath.lastIndexOf('/') + 1);
    const navLinks = document.querySelectorAll('.nav-links .nav-link');

    navLinks.forEach(link => {
        const linkHrefFile = link.getAttribute('href');
        link.classList.remove('active');
        if (linkHrefFile === currentPageFile) {
            link.classList.add('active');
            console.log(`Active page set: ${linkHrefFile}`);
        }
    });
};

const initializeUserDropdown = () => {
    const userDropdown = document.querySelector('.user-dropdown');
    if (!userDropdown) return;
    const dropdownContent = userDropdown.querySelector('.dropdown-content');

    userDropdown.addEventListener('click', function(event) {
        event.stopPropagation();
        dropdownContent.classList.toggle('show');
    });

    window.addEventListener('click', function(event) {
        if (!userDropdown.contains(event.target)) {
            dropdownContent.classList.remove('show');
        }
    });
};

const initializeOrderFormTabs = () => {
    const tabs = document.querySelectorAll('.order-tab');
    const supplierForm = document.getElementById('supplier-form');
    const customerForm = document.getElementById('customer-form');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const targetForm = tab.getAttribute('data-form');
            if (supplierForm) supplierForm.classList.add('hidden');
            if (customerForm) customerForm.classList.add('hidden');
            if (targetForm === 'supplier' && supplierForm) {
                supplierForm.classList.remove('hidden');
            } else if (targetForm === 'customer' && customerForm) {
                customerForm.classList.remove('hidden');
            }
        });
    });
};

const initializeSupplierModal = () => {
    const editButtons = document.querySelectorAll('.edit-supplier-btn');
    const modal = document.getElementById('edit-supplier-modal');
    if (!modal) return;

    const openModal = (supplierName) => {
        modal.querySelector('.supplier-name').textContent = supplierName;
        modal.classList.remove('hidden');
    };

    editButtons.forEach(button => {
        button.addEventListener('click', () => {
            const supplierCard = button.closest('.supplier-card');
            const supplierName = supplierCard ? supplierCard.querySelector('.supplier-name').textContent : 'Unknown Supplier';
            openModal(supplierName);
        });
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    });

    const saveButton = modal.querySelector('.save-edit-btn');
    if (saveButton) {
        saveButton.addEventListener('click', (event) => {
            event.preventDefault();
            alert("Supplier details saved!");
            modal.classList.add('hidden');
        });
    }

    const statusToggles = document.querySelectorAll('.status-toggle-group .status-toggle-btn');
    statusToggles.forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.closest('.status-toggle-group');
            group.querySelectorAll('.status-toggle-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
};

const initializeReportViewSwitching = () => {
    const hub = document.getElementById('report-hub');
    const detailContainer = document.getElementById('report-details-container');
    const backBtn = document.getElementById('report-back-btn');
    const reportCards = document.querySelectorAll('.report-card');
    const detailReports = document.querySelectorAll('.report-detail-card');
    if (!hub) return;

    const showHub = () => {
        hub.classList.remove('hidden');
        if (detailContainer) detailContainer.classList.add('hidden');
        if (backBtn) backBtn.classList.add('hidden');
        detailReports.forEach(report => report.classList.add('hidden'));
    };

    const showReport = (reportId) => {
        hub.classList.add('hidden');
        if (detailContainer) detailContainer.classList.remove('hidden');
        if (backBtn) backBtn.classList.remove('hidden');
        detailReports.forEach(report => report.classList.add('hidden'));
        const targetReport = document.getElementById(reportId);
        if (targetReport) {
            targetReport.classList.remove('hidden');
        }
    };

    reportCards.forEach(card => {
        card.addEventListener('click', () => {
            const reportId = card.getAttribute('data-report');
            showReport(reportId);
        });
    });

    if (backBtn) backBtn.addEventListener('click', showHub);
    showHub();
};

const initializeSettingViewSwitching = () => {
    const hub = document.getElementById('settings-hub');
    const detailContainer = document.getElementById('setting-details-container');
    const backBtn = document.getElementById('setting-back-btn');
    const settingCards = document.querySelectorAll('.setting-card');
    const detailSettings = document.querySelectorAll('.setting-detail-card');
    if (!hub) return;

    const showHub = () => {
        hub.classList.remove('hidden');
        if (detailContainer) detailContainer.classList.add('hidden');
        if (backBtn) backBtn.classList.add('hidden');
        detailSettings.forEach(setting => setting.classList.add('hidden'));
    };

    const showSetting = (settingId) => {
        hub.classList.add('hidden');
        if (detailContainer) detailContainer.classList.remove('hidden');
        if (backBtn) backBtn.classList.remove('hidden');
        detailSettings.forEach(setting => setting.classList.add('hidden'));
        const targetSetting = document.getElementById(settingId);
        if (targetSetting) {
            targetSetting.classList.remove('hidden');
        }
    };

    settingCards.forEach(card => {
        card.addEventListener('click', () => {
            const settingId = card.getAttribute('data-setting');
            showSetting(settingId);
        });
    });

    if (backBtn) backBtn.addEventListener('click', showHub);
    showHub();
};

const initializeUserModal = () => {
    const modal = document.getElementById('user-management-modal');
    if (!modal) return;

    const addButton = document.querySelector('#user-management .add-account-btn');
    const editButtons = document.querySelectorAll('#user-management .edit-btn');
    const roleToggles = document.querySelectorAll('.role-toggle-btn');
    const statusToggles = document.querySelectorAll('.user-status-toggle .status-toggle-btn');

    const openModal = () => {
        modal.classList.remove('hidden');
    };

    if (addButton) addButton.addEventListener('click', openModal);
    editButtons.forEach(button => button.addEventListener('click', openModal));

    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            modal.classList.add('hidden');
        }
    });

    roleToggles.forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.role-toggle-group').querySelectorAll('.role-toggle-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    statusToggles.forEach(btn => {
        btn.addEventListener('click', () => {
            const group = btn.closest('.user-status-toggle');
            group.querySelectorAll('.status-toggle-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    const createButton = modal.querySelector('.create-account-btn');
    if (createButton) {
        createButton.addEventListener('click', (event) => {
            event.preventDefault();
            alert("Account saved!");
            modal.classList.add('hidden');
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const loginButton = document.getElementById('login-btn');
    if (loginButton) {
        loginButton.addEventListener('click', () => {
            console.log("Login button clicked! Redirecting to Dashboard...");
            window.location.href = 'dashboard.html';
        });
    }

    const logoutButton = document.querySelector('.logout-btn-dropdown');
    if (logoutButton) {
        logoutButton.addEventListener('click', (event) => {
            console.log("Logout link clicked! Redirecting to Login...");
        });
    }

    setActiveSidebarLink();
    initializeUserDropdown();

    if (document.getElementById('orders-view')) {
        initializeOrderFormTabs();
    }
    
    if (document.getElementById('suppliers-view')) {
        initializeSupplierModal();
    }
    
    if (document.getElementById('reports-view')) {
        initializeReportViewSwitching();
    }
    
    if (document.getElementById('settings-view')) {
        initializeSettingViewSwitching();
        initializeUserModal();
    }
});