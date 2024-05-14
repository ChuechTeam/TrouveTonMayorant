const CONV_UPDATE_INTERVAL = 1000; // ms

/** @param {HTMLElement} element */
function initChatBox(element) {
    const state = {
        people: element.querySelector(".chat-people"),
        conversationSlot: element.querySelector(".-conversation-slot"),
        // L'élement HTML de la personne choisie (la partie de gauche)
        selectedPerson: null,
        // L'élément HTML de la conversation sélectionnée
        conversation: null,
        // Identifiant pour éviter de charger une conversation qu'on doit plus charger
        // (Par exemple quand on clique sur une conversation, puis une autre,
        // et que la requête de la 1ere est complétée après la 2e) 
        curConvLoadId: 0,

        init() {
            this.selectedPerson = this.people.querySelector(".chat-person.-selected");

            this.updatePersonLastMsgListen = this.updatePersonLastMsg.bind(this);
            this.updateConvFromSlot();

            // Changer de conversation quand une personne de la liste est cliquée
            this.people.addEventListener("click", e => {
                const p = e.target.closest(".chat-person");
                if (p !== null && p.dataset.id != null) {
                    // Alors on a cliqué sur une conversation
                    this.selectPerson(p, p.dataset.id);
                }
            })

            element.querySelector("#create-conv").addEventListener("click", function() {
                alert('C\'est pas implémenté... va dans profil et clique sur "Démarrer une discussion"');
            })
        },

        // Change de personne sélectionnée et charge la nouvelle conversation dans le slot
        selectPerson(element, id) {
            if (this.selectedPerson !== null && this.selectedPerson.dataset.id === id) {
                // Même conversation sélectionnée, ne rien faire
                return;
            }
            
            this.selectedPerson?.classList.remove("-selected");
            this.selectedPerson = element;
            element.classList.add("-selected");

            this.loadConv(id);
        },

        // Charge une conversation depuis le serveur, qui renvoie l'HTML de la conversation
        async loadConv(id) {
            const loadId = ++this.curConvLoadId;
            const html = await api.getConversation(id)
            if (loadId === this.curConvLoadId) {
                this.conversationSlot.innerHTML = html;
                this.updateConvFromSlot()
            }
        },

        // Initialise la conversation qui est dans le slot et ajoute/retire les listeners. 
        updateConvFromSlot() {
            const newConv = this.conversationSlot.firstElementChild;
            if (this.conversation === newConv) {
                return;
            } else if (this.conversation !== null) {
                // Retirer le listener de la conversation précédente
                this.conversation.removeEventListener("lastMessageUpdated", this.updatePersonLastMsgListen);
            }

            this.conversation = this.conversationSlot.firstElementChild;
            if (this.conversation != null) {
                initConversation(this.conversation);
                this.conversation.addEventListener("lastMessageUpdated", this.updatePersonLastMsgListen);
            }
        },

        // fonction qui est appelée quand la conversation reçoit un message
        // (on a besoin de deux variables sinon impossible de retirer le listener)
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
        updateHandle: null,
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
            
            // Planifier la maj 
            this.updateHandle = setTimeout(this.updateTick.bind(this), CONV_UPDATE_INTERVAL);

            this.scrollToBottom();
        },

        postMessage() {
            const content = this.elems.msgInput.value;
            // On évite d'envoyer un message vide
            if (content.trim() === "") {
                return;
            }

            // On retire le message de l'input
            this.elems.msgInput.value = "";

            // On envoie le message au serveur.
            api.sendMessage(this.id, content, this.lastSeenMsgId)
                .then(res => this.receiveMessages(res));
        },

        receiveMessages({html, firstMsgId, lastMsgId}) {
            console.log(`Receiving messages: [${firstMsgId}, ${lastMsgId}]`);

            // Pour savoir si on doit scroll tout en bas après ou non
            const scrollDown = this.scrollCloseEnough();
            // On met l'HTML reçu à la fin de la liste des messages
            this.elems.messages.insertAdjacentHTML("beforeend", html);

            // Supprimer les doublons (peut arriver si on a les requêtes dans le désordre)
            if (this.lastSeenMsgId !== null && firstMsgId <= this.lastSeenMsgId) {
                for (let i = this.elems.messages.children.length - 1; i >= 0; i--) {
                    const msg = this.elems.messages.children[i];

                    // Ancien message
                    if (msg.dataset.id <= this.lastSeenMsgId) {
                        msg.remove();
                    }

                    // Fin des messages envoyés par le serveur
                    if (msg.dataset.id <= firstMsgId) {
                        break;
                    }
                }
            }   
            
            this.lastSeenMsgId = lastMsgId

            // On envoie un événement pour mettre à jour le dernier message affiché sur la liste
            // des personnes.
            this.elems.root.dispatchEvent(new CustomEvent("lastMessageUpdated", {
                detail: {
                    txt: this.elems.messages.lastElementChild.querySelector(".-content").textContent
                }
            }))

            if (scrollDown) {
                this.scrollToBottom();
            }
        },

        // Renvoie true si le scroll est suffisamment proche de la fin des messages
        scrollCloseEnough() {
            const m = this.elems.messages;
            return m.scrollHeight - m.clientHeight - m.scrollTop < 50;
        },

        // Scrolle tout en bas des messages
        scrollToBottom() {
            const m = this.elems.messages;
            m.scrollTo(0, m.scrollHeight);
        },
        
        // Met à jour la liste des messages
        async updateTick() {
            // Si on a été retiré de l'écran, on arrête de faire des requêtes
            if (!this.alive) { return; }
            
            try {
                // Recevoir les messages de l'API (voir convMessages.php)
                const m = await api.getMessages(this.id, this.lastSeenMsgId);
                if (m !== null && this.alive) {
                    // Il y en a (et on est tjrs affiché), alors ajouter ces messages
                    this.receiveMessages(m);
                }
            } finally {
                // On relance la mise à jour après un certain temps (si on est tjrs affiché)
                if (this.alive) {
                    this.updateHandle = setTimeout(() => this.updateTick(), CONV_UPDATE_INTERVAL);
                }
            }
        },

        // False si la conversation n'est plus affichée à l'écran
        // (donc si on a changé de conversation)
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
     * @param {number} convId l'id de la conversation
     * @param {string} content le contenu du message
     * @param {number | null} since l'id du dernier message reçu
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
        
        if (res.status !== 200) {
            throw new Error("Failed to send message! Error code: " + res.status);
        }

        return {
            firstMsgId: parseInt(res.headers.get("First-Message-Id")),
            lastMsgId: parseInt(res.headers.get("Last-Message-Id")),
            html: await res.text()
        };
    },
    
    async getMessages(convId, since) {
        const endpoint = new URL("member-area/api/convMessages.php", root);
        endpoint.searchParams.set("id", convId);
        if (since != null) {
            endpoint.searchParams.set("since", since);
        }

        const res = await fetch(endpoint);
        if (res.status === 204) {
            // no content
            return null;
        }

        if (res.status !== 200) {
            throw new Error("Failed to send message! Error code: " + res.status);
        }

        return {
            firstMsgId: parseInt(res.headers.get("First-Message-Id")),
            lastMsgId: parseInt(res.headers.get("Last-Message-Id")),
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