// Abarrotería Los Nina — chatbot-widget.js (asistente informativo de productos y precios)
'use strict';

function initChatWidget() {
    const fab = document.getElementById('chat-fab');
    const panel = document.getElementById('chat-panel');
    const body = document.getElementById('chat-body');
    const input = document.getElementById('chat-input');
    const sendBtn = document.getElementById('chat-send');
    if (!fab || !panel || !body || !input) {
        return;
    }

    let typingEl = null;

    function scrollBottom() {
        body.scrollTop = body.scrollHeight;
    }

    function addBotMessage(texto) {
        const div = document.createElement('div');
        div.className = 'chat-msg bot';
        div.textContent = texto;
        body.appendChild(div);
        scrollBottom();
    }

    function addUserMessage(texto) {
        const div = document.createElement('div');
        div.className = 'chat-msg user';
        div.textContent = texto;
        body.appendChild(div);
        scrollBottom();
    }

    function showTyping() {
        typingEl = document.createElement('div');
        typingEl.className = 'chat-typing';
        typingEl.innerHTML = '<span></span><span></span><span></span>';
        body.appendChild(typingEl);
        scrollBottom();
    }

    function hideTyping() {
        if (typingEl) {
            typingEl.remove();
            typingEl = null;
        }
    }

    function sendMessage(mensaje) {
        addUserMessage(mensaje);
        showTyping();
        fetch('chatbot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mensaje: mensaje })
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                hideTyping();
                addBotMessage(data.respuesta || 'No entendí tu mensaje. ¿Podrías repetirlo?');
            })
            .catch(function () {
                hideTyping();
                addBotMessage('Estoy teniendo problemas para conectarme. Intenta de nuevo en unos momentos.');
            });
    }

    function closeChat() {
        panel.classList.remove('open');
        fab.style.display = 'flex';
    }

    fab.addEventListener('click', function () {
        panel.classList.add('open');
        fab.style.display = 'none';
        input.focus();
    });

    const closeBtn = document.getElementById('chat-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', closeChat);
    }

    function send() {
        const msg = input.value.trim();
        if (!msg) {
            return;
        }
        input.value = '';
        sendMessage(msg);
    }

    if (sendBtn) {
        sendBtn.addEventListener('click', send);
    }
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            send();
        }
    });

    window.LosNinaBot = {
        send: sendMessage
    };
}

document.addEventListener('DOMContentLoaded', function () {
    initChatWidget();
});
