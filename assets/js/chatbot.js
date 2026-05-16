/**
 * Nafas Chatbot Pro - Frontend JavaScript
 * Modern, Lightweight Chat UI
 */

(function () {
  'use strict';

  const NCP_STATE = {
    messages: [],
    sessionId: null,
    isLoading: false,
    config: null,
  };

  /**
   * Initialize chatbot
   */
  function init() {
    const mounts = document.querySelectorAll('[data-ncp-config]');
    mounts.forEach((mount) => {
      try {
        const config = JSON.parse(mount.getAttribute('data-ncp-config') || '{}');
        renderChatbot(mount, config);
      } catch (error) {
        console.error('Nafas Chatbot Pro Error:', error);
      }
    });
  }

  /**
   * Render chatbot UI
   */
  function renderChatbot(mount, config) {
    NCP_STATE.config = config;
    NCP_STATE.sessionId = generateSessionId();

    if (config.floating_mode) {
      renderFloatingChatbot(mount, config);
    } else {
      renderInlineChatbot(mount, config);
    }
  }

  /**
   * Render floating chatbot
   */
  function renderFloatingChatbot(mount, config) {
    const launcher = createLauncher(config);
    const panel = createPanel(config);

    mount.appendChild(launcher);
    mount.appendChild(panel);

    let isOpen = config.open_by_default || false;

    launcher.addEventListener('click', () => {
      isOpen = !isOpen;
      panel.style.display = isOpen ? 'flex' : 'none';
      launcher.style.display = isOpen ? 'none' : 'flex';
      if (isOpen) {
        restoreMessages();
      }
    });

    // Close button in panel
    const closeBtn = panel.querySelector('.ncp-header-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        isOpen = false;
        panel.style.display = 'none';
        launcher.style.display = 'flex';
      });
    }

    panel.style.display = isOpen ? 'flex' : 'none';
  }

  /**
   * Render inline chatbot
   */
  function renderInlineChatbot(mount, config) {
    const panel = createPanel(config);
    mount.appendChild(panel);
  }

  /**
   * Create launcher button
   */
  function createLauncher(config) {
    const launcher = document.createElement('button');
    launcher.className = `ncp-launcher ncp-launcher-${config.launcher_position}`;
    launcher.innerHTML = '💬';
    launcher.setAttribute(
      'aria-label',
      window.ncpGlobal?.i18n?.placeholder || 'Open chat'
    );
    launcher.setAttribute('title', 'Open chat');
    return launcher;
  }

  /**
   * Create chat panel
   */
  function createPanel(config) {
    const panel = document.createElement('div');
    panel.className = 'ncp-panel';
    panel.setAttribute('dir', config.text_direction || 'rtl');

    // Header
    const header = document.createElement('div');
    header.className = 'ncp-header';
    header.innerHTML = `
      <h3 class="ncp-header-title">${escapeHtml(config.company_name || 'Chat')}</h3>
      <button class="ncp-header-close" aria-label="Close">✕</button>
    `;

    // Messages container
    const messagesContainer = document.createElement('div');
    messagesContainer.className = 'ncp-messages';

    // Input area
    const inputArea = document.createElement('div');
    inputArea.className = 'ncp-input-area';
    inputArea.innerHTML = `
      <textarea 
        class="ncp-input" 
        placeholder="${escapeHtml(window.ncpGlobal?.i18n?.placeholder || 'Type message...')}"
        rows="1"
        aria-label="Message input"
      ></textarea>
      <button class="ncp-send-btn" aria-label="Send">📤</button>
    `;

    panel.appendChild(header);
    panel.appendChild(messagesContainer);
    panel.appendChild(inputArea);

    // Restore messages from storage
    restoreMessages();

    // Input event listeners
    const textarea = inputArea.querySelector('.ncp-input');
    const sendBtn = inputArea.querySelector('.ncp-send-btn');

    const autoResize = () => {
      textarea.style.height = 'auto';
      textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
    };

    textarea.addEventListener('input', autoResize);
    textarea.addEventListener('keypress', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });

    sendBtn.addEventListener('click', sendMessage);

    function sendMessage() {
      const message = textarea.value.trim();
      if (!message || NCP_STATE.isLoading) return;

      // Add user message
      addMessage(message, 'user', messagesContainer);
      textarea.value = '';
      autoResize();

      // Send to API
      NCP_STATE.isLoading = true;
      sendBtn.disabled = true;

      fetchChatResponse(message, config)
        .then((response) => {
          if (response.cached) {
            addMessage(response.message, 'assistant', messagesContainer);
          } else if (response.placeholder) {
            addMessage(response.message, 'error', messagesContainer);
          } else {
            addMessage(response.message, 'assistant', messagesContainer);
          }
        })
        .catch((error) => {
          addMessage(
            error.message || 'An error occurred. Please try again.',
            'error',
            messagesContainer
          );
        })
        .finally(() => {
          NCP_STATE.isLoading = false;
          sendBtn.disabled = false;
          saveMessages();
        });
    }

    return panel;
  }

  /**
   * Add message to conversation
   */
  function addMessage(text, role, container) {
    const messageEl = document.createElement('div');
    messageEl.className = `ncp-message ncp-message-${role}`;

    const contentEl = document.createElement('div');
    contentEl.className = 'ncp-message-content';

    if (role === 'error') {
      contentEl.textContent = '❌ ' + text;
      contentEl.style.color = '#d32f2f';
    } else {
      contentEl.textContent = text;
    }

    messageEl.appendChild(contentEl);
    container.appendChild(messageEl);
    container.scrollTop = container.scrollHeight;

    NCP_STATE.messages.push({ role, content: text, timestamp: Date.now() });
  }

  /**
   * Fetch chat response from server
   */
  function fetchChatResponse(message, config) {
    return new Promise((resolve, reject) => {
      const formData = new FormData();
      formData.append('action', 'ncp_chat');
      formData.append('message', message);
      formData.append('provider', config.provider || 'avalai');
      formData.append('model', config.model || 'gpt-4o-mini');
      formData.append('temperature', config.temperature || 0.7);
      formData.append('max_tokens', config.max_tokens || 512);
      formData.append('session_id', NCP_STATE.sessionId);
      formData.append('nonce', window.ncpGlobal?.nonce || '');

      // Get last few messages for context
      const history = NCP_STATE.messages
        .slice(-6)
        .map((msg) => ({
          role: msg.role,
          content: msg.content,
        }));

      formData.append('history', JSON.stringify(history));

      fetch(window.ncpGlobal?.ajaxUrl || '/wp-admin/admin-ajax.php', {
        method: 'POST',
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            resolve(data.data);
          } else {
            reject(new Error(data.data?.message || 'API Error'));
          }
        })
        .catch((error) => {
          reject(error);
        });
    });
  }

  /**
   * Save messages to localStorage
   */
  function saveMessages() {
    if (!NCP_STATE.config?.persist_history) return;
    try {
      localStorage.setItem(
        'ncp_messages_' + NCP_STATE.sessionId,
        JSON.stringify(NCP_STATE.messages)
      );
    } catch (e) {
      // Silently fail if localStorage not available
    }
  }

  /**
   * Restore messages from localStorage
   */
  function restoreMessages() {
    if (!NCP_STATE.config?.persist_history) return;
    try {
      const saved = localStorage.getItem('ncp_messages_' + NCP_STATE.sessionId);
      if (saved) {
        NCP_STATE.messages = JSON.parse(saved);
      }
    } catch (e) {
      // Silently fail if localStorage not available
    }
  }

  /**
   * Generate session ID
   */
  function generateSessionId() {
    return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  }

  /**
   * Escape HTML
   */
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose for testing
  window.NafahsChatbot = { init, NCP_STATE };
})();
