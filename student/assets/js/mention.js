class MentionSystem {
    constructor(textarea, options = {}) {
        this.textarea = textarea;
        this.options = {
            trigger: '@',
            minChars: 2,
            maxItems: 10,
            debounceTime: 300,
            ...options
        };
        
        this.currentMention = null;
        this.users = [];
        this.filteredUsers = [];
        this.selectedIndex = -1;
        this.dropdown = null;
        this.debounceTimer = null;
        
        this.init();
    }

    init() {
        this.createDropdown();
        this.bindEvents();
        this.loadUsers();
    }

    createDropdown() {
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'mention-dropdown';
        this.dropdown.style.display = 'none';
        this.textarea.parentNode.appendChild(this.dropdown);
    }

    bindEvents() {
        this.textarea.addEventListener('input', this.handleInput.bind(this));
        this.textarea.addEventListener('keydown', this.handleKeydown.bind(this));
        this.textarea.addEventListener('blur', this.handleBlur.bind(this));
        
        document.addEventListener('click', (e) => {
            if (!this.dropdown.contains(e.target) && e.target !== this.textarea) {
                this.hideDropdown();
            }
        });
    }

    async loadUsers() {
        try {
            const response = await fetch('api/users.php');
            this.users = await response.json();
        } catch (error) {
            console.error('Failed to load users:', error);
        }
    }

    handleInput(e) {
        const value = e.target.value;
        const cursorPos = e.target.selectionStart;
        
        // Find current mention
        const mention = this.findCurrentMention(value, cursorPos);
        
        if (mention) {
            this.currentMention = mention;
            this.filterUsers(mention.query);
            
            if (this.filteredUsers.length > 0) {
                this.showDropdown();
                this.renderDropdown();
            } else {
                this.hideDropdown();
            }
        } else {
            this.hideDropdown();
            this.currentMention = null;
        }
    }

    findCurrentMention(text, cursorPos) {
        const textBeforeCursor = text.substring(0, cursorPos);
        const triggerPos = textBeforeCursor.lastIndexOf(this.options.trigger);
        
        if (triggerPos === -1) return null;
        
        // Check if trigger is at word start or preceded by whitespace/newline
        const charBeforeTrigger = textBeforeCursor[triggerPos - 1];
        if (triggerPos > 0 && charBeforeTrigger && !/\s/.test(charBeforeTrigger)) {
            return null;
        }
        
        const query = textBeforeCursor.substring(triggerPos + 1);
        
        // Only trigger if query meets minimum length
        if (query.length < this.options.minChars) return null;
        
        // Check if query contains whitespace (mention ended)
        if (/\s/.test(query)) return null;
        
        return {
            start: triggerPos,
            end: cursorPos,
            query: query.toLowerCase()
        };
    }

    filterUsers(query) {
        this.filteredUsers = this.users
            .filter(user => 
                user.name.toLowerCase().includes(query) ||
                user.email.toLowerCase().includes(query)
            )
            .slice(0, this.options.maxItems);
        this.selectedIndex = 0;
    }

    showDropdown() {
        this.dropdown.style.display = 'block';
        this.positionDropdown();
    }

    hideDropdown() {
        this.dropdown.style.display = 'none';
        this.selectedIndex = -1;
    }

    positionDropdown() {
        const textareaRect = this.textarea.getBoundingClientRect();
        const cursorPos = this.getCursorPosition();
        
        this.dropdown.style.left = cursorPos.left + 'px';
        this.dropdown.style.top = (cursorPos.bottom + 5) + 'px';
    }

    getCursorPosition() {
        const textareaRect = this.textarea.getBoundingClientRect();
        const text = this.textarea.value;
        const cursorPos = this.textarea.selectionStart;
        
        // Create temporary div to measure cursor position
        const div = document.createElement('div');
        const computedStyle = window.getComputedStyle(this.textarea);
        
        // Copy textarea styles
        ['fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'lineHeight', 'letterSpacing', 'textTransform', 'wordSpacing', 'paddingLeft', 'paddingTop', 'paddingRight', 'paddingBottom', 'borderLeftWidth', 'borderTopWidth', 'borderRightWidth', 'borderBottomWidth', 'width'].forEach(prop => {
            div.style[prop] = computedStyle[prop];
        });
        
        div.style.position = 'absolute';
        div.style.visibility = 'hidden';
        div.style.whiteSpace = 'pre-wrap';
        div.style.wordWrap = 'break-word';
        
        // Set text before cursor
        div.textContent = text.substring(0, cursorPos);
        
        // Add span at cursor position
        const span = document.createElement('span');
        span.textContent = '|';
        div.appendChild(span);
        
        document.body.appendChild(div);
        
        const spanRect = span.getBoundingClientRect();
        const result = {
            left: spanRect.left - textareaRect.left,
            bottom: spanRect.bottom - textareaRect.top
        };
        
        document.body.removeChild(div);
        return result;
    }

    renderDropdown() {
        const items = this.filteredUsers.map((user, index) => `
            <div class="mention-item ${index === this.selectedIndex ? 'selected' : ''}" data-index="${index}">
                <img src="${user.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.name)}&background=1abc9c&color=fff`}" 
                     alt="${user.name}" class="mention-avatar">
                <div class="mention-info">
                    <div class="mention-name">${this.highlightMatch(user.name, this.currentMention.query)}</div>
                    <div class="mention-email">${this.highlightMatch(user.email, this.currentMention.query)}</div>
                </div>
            </div>
        `).join('');
        
        this.dropdown.innerHTML = items;
        
        // Bind click events
        this.dropdown.querySelectorAll('.mention-item').forEach(item => {
            item.addEventListener('click', () => {
                const index = parseInt(item.dataset.index);
                this.selectUser(this.filteredUsers[index]);
            });
        });
    }

    highlightMatch(text, query) {
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    }

    handleKeydown(e) {
        if (!this.dropdown.style.display || this.dropdown.style.display === 'none') return;
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.filteredUsers.length - 1);
                this.renderDropdown();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                this.renderDropdown();
                break;
                
            case 'Enter':
            case 'Tab':
                e.preventDefault();
                if (this.selectedIndex >= 0 && this.filteredUsers[this.selectedIndex]) {
                    this.selectUser(this.filteredUsers[this.selectedIndex]);
                }
                break;
                
            case 'Escape':
                e.preventDefault();
                this.hideDropdown();
                break;
        }
    }

    handleBlur() {
        setTimeout(() => this.hideDropdown(), 200);
    }

    selectUser(user) {
        const text = this.textarea.value;
        const beforeMention = text.substring(0, this.currentMention.start);
        const afterMention = text.substring(this.currentMention.end);
        
        const mentionText = `@${user.name.replace(/\s/g, '')} `;
        this.textarea.value = beforeMention + mentionText + afterMention;
        
        // Set cursor position after mention
        const newCursorPos = this.currentMention.start + mentionText.length;
        this.textarea.setSelectionRange(newCursorPos, newCursorPos);
        
        this.hideDropdown();
        
        // Trigger change event
        this.textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    getMentions() {
        const text = this.textarea.value;
        const mentions = [];
        const regex = /@(\w+)/g;
        let match;
        
        while ((match = regex.exec(text)) !== null) {
            const username = match[1];
            const user = this.users.find(u => u.name.replace(/\s/g, '') === username);
            if (user) {
                mentions.push({
                    username: username,
                    user_id: user.id,
                    name: user.name,
                    start: match.index,
                    end: match.index + match[0].length
                });
            }
        }
        
        return mentions;
    }
}

// Auto-initialize on textareas with mention class
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('textarea.mention-enabled').forEach(textarea => {
        new MentionSystem(textarea);
    });
});
