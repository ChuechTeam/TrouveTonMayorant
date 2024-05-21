import { api } from "./api.js";
import { typeset } from "./math.js";

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
                if (e.target.classList.contains("-profile-link")) {
                    return;
                }

                const p = e.target.closest(".chat-person");
                if (p !== null && p.dataset.id != null) {
                    // Alors on a cliqué sur une conversation
                    this.selectPerson(p, p.dataset.id);
                }
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
export function initConversation(element) {
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
            if (this.elems.msgForm !== null) {
                this.elems.msgForm.addEventListener("submit", e => {
                    e.preventDefault();
                    this.postMessage();
                });
            }

            if (this.elems.messages.children.length > 0) {
                this.lastSeenMsgId = this.elems.messages.lastElementChild.dataset.id;
            }

            // Delete a message when clicking on the "delete" button
            // Or report it if we click on the "report" button
            this.elems.messages.addEventListener("click", e => {
                if (e.target.classList.contains("-delete")) {
                    // find the parent message element
                    const msg = e.target.closest(".chat-message");
                    if (msg != null) {
                        if (confirm("Voulez-vous vraiment supprimer ce message ?")) {
                            this.deleteMessage(msg);
                        }
                    }
                } else if (e.target.classList.contains("-report")) {
                    const msg = e.target.closest(".chat-message");
                    if (msg != null) {
                        let r = prompt("Écrivez en quoi ce message est problématique afin de le signaler.")
                        r = r?.trim(); // remove excess spaces (so we don't allow sending a message with only spaces)
                        if (r) { // make sure it is not empty, and not null
                            this.reportMessage(msg, r);
                        }
                    }
                }
            })

            // Planifier la maj 
            this.updateHandle = setTimeout(this.updateTick.bind(this), CONV_UPDATE_INTERVAL);

            this.scrollToBottom();

            typeset(() => [this.elems.root]);
        },

        // Sends a message to the server.
        // Called by the message send form and button.
        postMessage() {
            const content = this.elems.msgInput.value;
            // Dont allow sending empty messages
            if (content.trim() === "") {
                return;
            }

            // Clear out the input field
            this.elems.msgInput.value = "";

            // And send the message to the server.
            // The HTML of the message will be returned and appended to the conversation.
            api.sendMessage(this.id, content, this.lastSeenMsgId)
                .then(res => this.receiveMessages(res));
        },

        // Receives messages from the server and appends them to the conversation.
        // Called by the periodic message update function, and when a message has successfully been sent.
        receiveMessages({ html, firstMsgId, lastMsgId }) {
            console.log(`Receiving messages: [${firstMsgId}, ${lastMsgId}]`);

            // First know if we should scroll down or not, before adding HTML which will
            // change the scrolling position
            const scrollDown = this.scrollCloseEnough();

            // Append the received HTML at the end of the message list.
            this.elems.messages.insertAdjacentHTML("beforeend", html);

            // Go through all messages sent by the server to refresh the equations.
            // And also remove duplicates (can happen if we have requests out of order)
            const messages = [];
            for (let i = this.elems.messages.children.length - 1; i >= 0; i--) {
                const msg = this.elems.messages.children[i];
                const msgId = msg.dataset.id;

                // This is an old message, we need to remove it
                if (this.lastSeenMsgId !== null && msgId <= this.lastSeenMsgId) {
                    msg.remove();
                } else {
                    // Add it to the list of messages to be refreshed.
                    messages.push(msg);
                }

                // This is the last message sent by the server, stop there.
                if (msg.dataset.id <= firstMsgId) {
                    break;
                }
            }

            // Refresh MathJax to render equations on newly added messages.
            typeset(messages);
            
            this.lastSeenMsgId = lastMsgId

            // Dispatch an event to update the last message on the people list (on the left)
            this.elems.root.dispatchEvent(new CustomEvent("lastMessageUpdated", {
                detail: {
                    txt: this.elems.messages.lastElementChild.querySelector(".-content").textContent
                }
            }))

            // Remember when we did the scroll down check? Now it's time to do it,
            // if the user had the scrollbar nearly at the bottom.
            if (scrollDown) {
                this.scrollToBottom();
            }
        },

        // Delete a message.
        // Called when the user clicks on a message's delete button.
        deleteMessage(msgElement) {
            const msgId = parseInt(msgElement.dataset.id);
            api.deleteMessage(this.id, msgId)
                .then(() => msgElement.remove());
        },

        // Submits a report for a specified message, with a reason
        // Called when the user clicks on a message's report button, and fills out the form.
        reportMessage(msgElement, reason) {
            const msgId = parseInt(msgElement.dataset.id);
            api.reportMessage(this.id, msgId, reason)
                .then(() => alert("Signalement envoyé !"));
        },

        // Returns true when the message list is scrolled close enough to the bottom
        scrollCloseEnough() {
            const m = this.elems.messages;
            return m.scrollHeight - m.clientHeight - m.scrollTop < 50;
        },

        // Scrolls at the very bottom of the message list
        scrollToBottom() {
            const m = this.elems.messages;
            m.scrollTo(0, m.scrollHeight);
        },

        // Asks the server for new messages, and schedules another update after we've got a response of the server.
        async updateTick() {
            // If we've been removed from the DOM, stop sending requests to the server
            if (!this.alive) {
                return;
            }

            try {
                // Receive messages from the API (see member-area/api/convMessages.php)
                const m = await api.getMessages(this.id, this.lastSeenMsgId);
                if (m !== null && this.alive) {
                    // There's some new messages, let's add them!
                    this.receiveMessages(m);
                }
            } finally {
                // Schedule another update in CONV_UPDATE_INTERVAL milliseconds.
                if (this.alive) {
                    this.updateHandle = setTimeout(() => this.updateTick(), CONV_UPDATE_INTERVAL);
                }
            }
        },

        // Returns false when the element is not in the DOM anymore, meaning that it is not displayed
        // on screen, and has likely been "destroyed".
        get alive() {
            return this.elems.root.isConnected;
        }
    }

    state.init();
    element.convState = state;
}

/**
 * Conversation Dialog
 * Uses a custom element to display a conversation in a dialog box.
 */
const convDialogTemplate = document.createElement("template");
convDialogTemplate.innerHTML = `
<link rel="stylesheet" href="/assets/style.css">
<style>
#dialog {
    display: flex;
    flex-direction: column;
    padding: 0;

    & > .-header {
        display: flex;
        & > .-title {
            margin: 4px 8px;
            margin-bottom: 2px;
            flex-grow: 1;
            font-size: 1.33em;
        }
        & > .-close {
            border: none;
            background-color: transparent;
            & > .-icon {
                display: block;
                margin: auto 0;
                font-size: 1.5em;
                color: white; 
                pointer-events: none; 
            }
            &:hover {
                background-color: rgba(255, 255, 255, 0.3);
            }
            &:active {
                background-color: rgba(255, 255, 255, 0.2);
            }
        }

        background-color: #a30000;
        color: white;
    }

    & > .-slot {
        display: contents;
    }

    border: 1px solid rgb(129, 129, 129);
    border-radius: 4px;

    height: 95%;
    width: 90%;

    box-shadow: 0px 0px 5px 1px rgba(0, 0, 0, 0.2);

    /** Mobile */
    @media (max-width: 768px) {
        width: 98%;
    }
}

#dialog:not([open]) {
    display: none;
}

::slotted(.chat-conversation) {
    flex-grow: 1;
    padding: 8px;
    overflow: auto;
}
</style>
<dialog id="dialog">
    <form class="-header" method="dialog">
    <h2 class="-title">Conversation</h2>
    <button class="-close"><div class="material-symbols-rounded -icon">close</div> </button>
    </form>
    <slot></slot>
</dialog>
`;
export class ConversationDialog extends HTMLElement {
    constructor() {
        super();
        this.dom = this.attachShadow({ mode: "open" });
        this.dom.replaceChildren(convDialogTemplate.content.cloneNode(true));
        this.dialog = this.dom.getElementById("dialog");
        this.dialog.addEventListener("close", () => this.remove());
    }

    show(html) {
        this.dialog.showModal();
        this.innerHTML = html;
        initConversation(this.querySelector(".chat-conversation"));
    }
}
customElements.define("conversation-dialog", ConversationDialog);

export async function fetchAndOpenConv(convId) {
    const conversationHTML = await api.getConversation(convId);
    const dialog = document.body.appendChild(new ConversationDialog());
    dialog.show(conversationHTML);
}

/*
 * Initialising the chat box of the chat.php page.
 */
const box = document.getElementById("chat-box")
if (box && !box.classList.contains("-not-sup")) {
    initChatBox(box);
}