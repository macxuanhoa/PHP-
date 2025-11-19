class ToastManager {
    constructor() {
        this.container = null;
        this.toasts = new Map();
        this.init();
    }

    init() {
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        }
    }

    show(message, type = 'info', options = {}) {
        const {
            title = null,
            duration = 5000,
            closable = true,
            icon = null
        } = options;

        const toastId = Date.now() + Math.random();
        const toast = this.createToast(toastId, message, type, title, closable, icon);
        
        this.container.appendChild(toast);
        this.toasts.set(toastId, toast);

        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 10);

        // Auto remove
        if (duration > 0) {
            setTimeout(() => this.remove(toastId), duration);
        }

        return toastId;
    }

    createToast(id, message, type, title, closable, customIcon) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.dataset.toastId = id;

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const icon = customIcon || icons[type] || icons.info;

        let html = `
            <i class="fas ${icon} toast-icon"></i>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${title}</div>` : ''}
                <div class="toast-message">${message}</div>
            </div>
        `;

        if (closable) {
            html += `<button class="toast-close" onclick="toastManager.remove(${id})">&times;</button>`;
        }

        toast.innerHTML = html;
        return toast;
    }

    remove(id) {
        const toast = this.toasts.get(id);
        if (toast) {
            toast.classList.add('hide');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                this.toasts.delete(id);
            }, 300);
        }
    }

    success(message, options = {}) {
        return this.show(message, 'success', options);
    }

    error(message, options = {}) {
        return this.show(message, 'error', options);
    }

    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }

    info(message, options = {}) {
        return this.show(message, 'info', options);
    }

    clear() {
        this.toasts.forEach((toast, id) => this.remove(id));
    }
}

// Global instance
const toastManager = new ToastManager();

// Helper functions for backward compatibility
function showToast(message, type = 'info', title = null) {
    return toastManager.show(message, type, { title });
}

function showSuccess(message, title = null) {
    return toastManager.success(message, { title });
}

function showError(message, title = null) {
    return toastManager.error(message, { title });
}

function showWarning(message, title = null) {
    return toastManager.warning(message, { title });
}

function showInfo(message, title = null) {
    return toastManager.info(message, { title });
}
