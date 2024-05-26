import {api} from "./api.js";
import {typeset} from "./math.js";

/** Interval at which the conversation will poll the server for new messages. */
const CONV_UPDATE_INTERVAL = 1000; // ms

/**
 * Initializes the chat box, making its people list interactive.
 * @param {HTMLElement} element
 * */
function initChatBox(element) {
    const state = {
        people: element.querySelector(".chat-people"),
        conversationSlot: element.querySelector(".-conversation-slot"),
        // The HTML element of the selected person (the left part)
        selectedPerson: null,
        // The HTML element of the selected conversation
        conversation: null,
        // Identifier to avoid loading a conversation that we shouldn't load anymore
        // (For example when we click on a conversation, then another,
        // and the request of the first one is completed after the second one)
        curConvLoadId: 0,

        init() {
            this.selectedPerson = this.people.querySelector(".chat-person.-selected");

            this.updatePersonLastMsgListener = this.updatePersonLastMsg.bind(this);
            this.updateConvFromSlot();

            // Change the displayede conversation when clicking on a person
            this.people.addEventListener("click", e => {
                if (e.target.classList.contains("-profile-link")) {
                    return;
                }

                const p = e.target.closest(".chat-person");
                if (p !== null && p.dataset.id != null) {
                    // Then we actually have clicked on a conversation.
                    this.selectPerson(p, p.dataset.id);
                }
            })
        },

        // Change the selected person, and update the conversation slot
        selectPerson(element, id) {
            if (this.selectedPerson !== null && this.selectedPerson.dataset.id === id) {
                // We've selected the same one, no need to do anything
                return;
            }

            // Move the "-selected" class from the previously selected person to the new one.
            this.selectedPerson?.classList.remove("-selected");
            this.selectedPerson = element;
            element.classList.add("-selected");

            this.loadConv(id);
        },

        // Loads a conversation from the server and puts it in the conversation slot
        async loadConv(id) {
            const loadId = ++this.curConvLoadId;
            const html = await api.getConversation(id)
            if (loadId === this.curConvLoadId) {
                this.conversationSlot.innerHTML = html;
                this.updateConvFromSlot()
            }
        },

        // Initializes the conversation from the conversation slot, and manages the listeners
        updateConvFromSlot() {
            const newConv = this.conversationSlot.firstElementChild;
            if (this.conversation === newConv) {
                return;
            } else if (this.conversation !== null) {
                // Remove the listener from the previous conversation
                this.conversation.removeEventListener("lastMessageUpdated", this.updatePersonLastMsgListener);
            }

            this.conversation = this.conversationSlot.firstElementChild;
            if (this.conversation != null) {
                initConversation(this.conversation);
                this.conversation.addEventListener("lastMessageUpdated", this.updatePersonLastMsgListener);
            }
        },

        // Function called when a the conversation's last message has been updated.
        // We need to use this weird updatePersonLastMsgListener variable to be able to remove
        // a listener from the old conversation.
        updatePersonLastMsgListener: null,
        updatePersonLastMsg(e) {
            const txt = e.detail.txt;
            this.selectedPerson.querySelector(".-last-msg").textContent = txt;
        }
    }

    state.init();
    element.chatState = state;
}

/**
 * Initializes a conversation element, making it ready to receive and post messages.
 * @param {HTMLElement} element
 **/
export function initConversation(element) {
    // Retrieve the id from the data-id="" attribute.
    const id = element.dataset.id;
    if (id == null) {
        // No need to initialise this conversation, it's empty!
        return;
    }

    const state = {
        /** @type {string} ID of the conversation */
        id,
        /** @type {number|null}
         * ID of the last seen message; when the last message is deleted,
         * this should change to the last non-deleted message id (null if the conversation is empty)  */
        lastSeenMsgId: null,
        /** @type {Set<number>} IDs of messages that the server has told us are deleted */
        deletedMessagesIds: new Set(),
        /** @type {number|null}
         * Timeout handle for the updateTick function, used to stop updating the conversation
         * when the DOM node has been destroyed */
        updateHandle: null,
        elems: {
            root: element,
            messages: element.querySelector(".-messages"),
            msgForm: element.querySelector(".-send"),
            msgInput: element.querySelector(".-msg-input")
        },

        init() {
            // Make the message submit form post a message asynchonously,
            // instead of reloading the page.
            if (this.elems.msgForm !== null) {
                this.elems.msgForm.addEventListener("submit", e => {
                    e.preventDefault();
                    this.postMessage();
                });
            }

            // Find the last message id by taking the last message in the DOM
            if (this.elems.messages.children.length > 0) {
                this.lastSeenMsgId = parseInt(this.elems.messages.lastElementChild.dataset.id);
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

            // Schedule a conversation update (to gather new messages) every CONV_UPDATE_INTERVAL ms.
            this.updateHandle = setTimeout(this.updateTick.bind(this), CONV_UPDATE_INTERVAL);

            // Make sure we're at the bottom of the message list
            this.scrollToBottom();

            // Refresh MathJax typesetting for all messages.
            typeset(() => [this.elems.root]);
        },

        // Sends a message to the server.
        // Called by the message send form and button.
        postMessage() {
            // Retrieve the message content from the input field.
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
                .then(res => {
                    // Add the messages to the DOM
                    this.receiveMessages(res);
                    // Remove any deleted messages from the DOM (the array is empty by default)
                    this.receiveMessageDeletion(res.deletedMessages);
                });
        },

        // Receives messages from the server and appends them to the conversation.
        // Called by the periodic message update function, and when a message has successfully been sent.
        receiveMessages({html, firstMsgId, lastMsgId}) {
            console.log(`Receiving messages: [${firstMsgId}, ${lastMsgId}]`);

            // First know if we should scroll down or not, before adding HTML which will
            // change the scrolling position
            const scrollDown = this.scrollCloseEnough();

            // Append the received HTML at the end of the message list.
            this.elems.messages.insertAdjacentHTML("beforeend", html);

            // Go through all messages sent by the server to refresh the equations.
            // And also remove duplicates and deleted messages (can happen if we have requests out of order)
            const messages = [];
            for (let i = this.elems.messages.children.length - 1; i >= 0; i--) {
                const msg = this.elems.messages.children[i];
                const msgId = parseInt(msg.dataset.id);

                let remove = false;
                // Remove it if we've already seen this message.
                remove ||= this.lastSeenMsgId !== null && msgId <= this.lastSeenMsgId;
                // Remove it if we know that this message has been deleted.
                remove ||= this.deletedMessagesIds.has(msgId);

                if (remove) {
                    // Remove it from the DOM.
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

            // Update the last seen message id.
            this.lastSeenMsgId = lastMsgId

            // Dispatch an event to update the last message on the people list (on the left)
            // This event will be handled by the chat box.
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
                .then(() => this.receiveMessageDeletion([msgId]));
        },

        // Acknowledges that messages in the ids array have been deleted,
        // and removes them from the conversation if they exist.
        receiveMessageDeletion(ids) {
            for (const id of ids) {
                if (this.deletedMessagesIds.has(id)) {
                    // We already know that this one is deleted, don't do anything.
                    continue;
                }

                // Register this id as a deleted message.
                this.deletedMessagesIds.add(id);

                // Remove the message from the DOM, and update the last seen message.
                const c = this.elems.messages.children;
                if (this.lastSeenMsgId != id) {
                    // This isn't the last message, so let's find it from the bottom up (since it's more likely),
                    // and remove it from the DOM.
                    for (let i = c.length - 1; i >= 0; i--) {
                        if (c[i].dataset.id == id) { // == to allow for string comparison
                            c[i].remove();
                            break;
                        }
                    }
                } else { // this.lastSeenMsgId == id
                    // The last message has been deleted!
                    // We need to remove the last message element from the DOM,
                    // and update the last seen message id.
                    c[c.length - 1].remove();
                    // We've removed the last message, so take the now-last message's id, if it exists.
                    this.lastSeenMsgId = c.length !== 0 ? parseInt(c[c.length - 1].dataset.id) : null;
                }
            }
        },

        // Submits a report for a specified message, with a reason
        // Called when the user clicks on a message's report button, and fills out the form.
        reportMessage(msgElement, reason) {
            const msgId = parseInt(msgElement.dataset.id);
            api.reportMessage(this.id, msgId, reason)
                .then(() => alert("Signalement envoyé !"))
                .catch(() => alert("Échec de l'envoi du signalement !"));
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

            // Use a try/finally block to still update messages after getMessages fails.
            try {
                // Receive messages from the API (see member-area/api/convMessages.php)
                const m = await api.getMessages(this.id, this.lastSeenMsgId);
                // Are we still displayed on screen?
                if (this.alive) {
                    // There's some new messages, let's add them!
                    if (m.hasContent) {
                        this.receiveMessages(m);
                    }

                    // Make sure to delete any messages that have been deleted.
                    this.receiveMessageDeletion(m.deletedMessages);
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
<link rel="stylesheet" href="/assets/style/_root.css">
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
            & > .icon {
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
        <button class="-close"><div class="icon">close</div></button>
    </form>
    <slot></slot>
</dialog>
`;

export class ConversationDialog extends HTMLElement {
    constructor() {
        super();
        this.dom = this.attachShadow({mode: "open"});
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

/**
 * Fetches a conversation from the server and displays it in a dialog box.
 * @param {string} convId the conversation id to fetch
 * @return {Promise<void>}
 */
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