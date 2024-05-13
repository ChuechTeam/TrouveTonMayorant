/** @param {HTMLElement} element */
function initChatBox(element) {
    const state = {
        people: element.querySelector(".chat-people"),
        conversationSlot: element.querySelector(".-conversation-slot"),
        selectedPerson: null,
        conversation: null,
        curConvLoadId: 0,

        init() {
            this.selectedPerson = this.people.querySelector(".chat-person.-selected");

            this.updatePersonLastMsgListen = this.updatePersonLastMsg.bind(this);
            this.updateConvFromSlot();

            this.people.addEventListener("click", e => {
                const p = e.target.closest(".chat-person");
                if (p !== null && p.dataset.id != null) {
                    this.selectPerson(p, p.dataset.id);
                }
            })

            element.querySelector("#create-conv").addEventListener("click", function() {
                alert('C\'est pas implémenté... va dans profil et clique sur "Démarrer une discussion"');
            })
        },

        selectPerson(element, id) {
            this.selectedPerson?.classList.remove("-selected");
            this.selectedPerson = element;
            element.classList.add("-selected");

            this.loadConv(id);
        },

        async loadConv(id) {
            const loadId = ++this.curConvLoadId;
            const html = await api.getConversation(id)
            if (loadId === this.curConvLoadId) {
                this.conversationSlot.innerHTML = html;
                this.updateConvFromSlot()
            }
        },

        updateConvFromSlot() {
            const newConv = this.conversationSlot.firstElementChild;
            if (this.conversation === newConv) {
                return;
            } else if (this.conversation !== null) {
                this.conversation.removeEventListener("lastMessageUpdated", this.updatePersonLastMsgListen);
            }

            this.conversation = this.conversationSlot.firstElementChild;
            if (this.conversation != null) {
                initConversation(this.conversation);
                this.conversation.addEventListener("lastMessageUpdated", this.updatePersonLastMsgListen);
            }
        },

        updatePersonLastMsgListen: null,
        updatePersonLastMsg(e) {
            const txt = e.detail.txt;
            this.selectedPerson.querySelector(".-last-msg").textContent = txt;
        }
    }

    state.init();
    element.chatState = state;
}

/** @param {HTMLElement} element */
function initConversation(element) {
    const id = element.dataset.id;
    if (id == null) {
        return;
    }

    const state = {
        id,
        lastSeenMsgId: null,
        elems: {
            root: element,
            messages: element.querySelector(".-messages"),
            msgForm: element.querySelector(".-send"),
            msgInput: element.querySelector(".-msg-input")
        },

        init() {
            if (this.elems.msgForm == null) {
                return; // surement un admin
            }

            this.elems.msgForm.addEventListener("submit", e => {
                e.preventDefault();
                this.postMessage();
            });
            if (this.elems.messages.children.length > 0) {
                this.lastSeenMsgId = this.elems.messages.lastElementChild.dataset.id;
            }

            this.scrollToBottom(true);
        },

        postMessage() {
            const content = this.elems.msgInput.value;
            if (content.trim() === "") {
                return;
            }

            api.sendMessage(this.id, content, this.lastSeenMsgId)
                .then(res => {
                    this.receiveMessages(res);
                    this.elems.msgInput.value = "";
                });
        },

        receiveMessages({html, firstMsgId, lastMsgId}) {
            const scrollDown = this.scrollCloseEnough();
            this.elems.messages.insertAdjacentHTML("beforeend", html);

            // Supprimer les doublons
            if (this.lastSeenMsgId !== null && firstMsgId <= this.lastSeenMsgId) {
                for (let i = this.elems.messages.children.length - 1; i >= 0; i--) {
                    const msg = this.elems.messages.children[i];
                    if (msg.dataset.id <= this.lastSeenMsgId) {
                        msg.remove();
                    } else {
                        break;
                    }
                }
            }
            if (lastMsgId > this.lastSeenMsgId) {
                this.lastSeenMsgId = lastMsgId
            }

            this.elems.root.dispatchEvent(new CustomEvent("lastMessageUpdated", {
                detail: {
                    txt: this.elems.messages.lastElementChild.querySelector(".-content").textContent
                }
            }))

            if (scrollDown) {
                this.scrollToBottom();
            }
        },

        scrollCloseEnough() {
            const m = this.elems.messages;
            return m.scrollHeight - m.clientHeight - m.scrollTop < 50;
        },

        scrollToBottom() {
            const m = this.elems.messages;
            m.scrollTo(0, m.scrollHeight);
        },

        get alive() {
            return this.elems.root.isConnected;
        }
    }

    state.init();
    element.convState = state;
}

const box = document.getElementById("chat-box")
if (box) {
    initChatBox(box);
}

const root = new URL(window.location.origin);
const api = {
    /**
     * Envoie un message
     * @param {number} convId
     * @param {string} content
     * @param {number | null} since
     */
    async sendMessage(convId, content, since) {
        const endpoint = new URL("member-area/api/convMessages.php", root);
        endpoint.searchParams.set("id", convId);
        if (since != null) {
            endpoint.searchParams.set("since", since);
        }

        const res = await fetch(endpoint, {
            method: "POST",
            body: JSON.stringify({content}),
            headers: {
                "Content-Type": "application/json"
            }
        });

        return {
            firstMsgId: res.headers.has("First-Message-Id") ? parseInt(res.headers.get("First-Message-Id")) : null,
            lastMsgId: res.headers.has("Last-Message-Id") ? parseInt(res.headers.get("Last-Message-Id")) : null,
            html: await res.text()
        };
    },

    async getConversation(convId) {
        const endpoint = new URL("member-area/api/conversations.php", root);
        endpoint.searchParams.set("id", convId);

        const res = await fetch(endpoint);
        return await res.text();
    }
}