const root = new URL(window.location.origin);
export const api = {
    /**
     * Sends a message to a conversation
     * @param {number} convId the conversation id
     * @param {string} content contents of the message
     * @param {number | null} since the id of the last received message, to filter out old messages
     */
    async sendMessage(convId, content, since) {
        const endpoint = new URL("member-area/api/convMessages.php", root);
        // Fill URL parameters: ?id and ?since
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

        // TODO: make use of the Is-Blocked header
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

    async deleteMessage(convId, msgId) {
        const endpoint = new URL("member-area/api/convMessages.php", root);
        endpoint.searchParams.set("id", convId);
        endpoint.searchParams.set("msgId", msgId);

        const res = await fetch(endpoint, {
            method: "DELETE"
        });

        if (res.status !== 200) {
            throw new Error("Failed to delete message! Error code: " + res.status);
        }
    },

    async getConversation(convId) {
        const endpoint = new URL("member-area/api/conversations.php", root);
        endpoint.searchParams.set("id", convId);

        const res = await fetch(endpoint);
        return await res.text();
    },
    
    async reportMessage(convId, msgId, reason) {
        const endpoint = new URL("member-area/api/reports.php", root);

        const res = await fetch(endpoint, {
            method: "POST",
            body: JSON.stringify({convId, msgId, reason}),
            headers: {
                "Content-Type": "application/json"
            }
        });
        
        if (res.status !== 200) {
            throw new Error("Failed to report message! Error code: " + res.status);
        }
    },

    async deleteReport(reportId) {
        const url = new URL(`/member-area/api/reports.php?id=${reportId}`, root);
        const response = await fetch(url, {
            method: 'DELETE',
        });
        if (!response.ok) {
            throw new Error('Failed to delete report');
        }
    }
}