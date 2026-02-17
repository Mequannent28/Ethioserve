<!-- EthioServe AI Chatbot Widget -->
<style>
    /* Chatbot Button */
    .chatbot-toggler {
        position: fixed;
        bottom: 30px;
        right: 35px;
        outline: none;
        border: none;
        height: 60px;
        width: 60px;
        display: flex;
        cursor: pointer;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #1B5E20;
        transition: all 0.3s ease;
        z-index: 9999;
        box-shadow: 0 4px 15px rgba(27, 94, 32, 0.4);
    }

    .chatbot-toggler:hover {
        transform: scale(1.1);
        background: #2E7D32;
    }

    .chatbot-toggler span {
        color: #fff;
        position: absolute;
    }

    .chatbot-toggler span:last-child,
    body.show-chatbot .chatbot-toggler span:first-child {
        opacity: 0;
    }

    body.show-chatbot .chatbot-toggler span:last-child {
        opacity: 1;
    }

    /* Chatbot Window */
    .chatbot {
        position: fixed;
        right: 35px;
        bottom: 100px;
        width: 380px;
        background: #fff;
        border-radius: 15px;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
        transform: scale(0.5);
        transform-origin: bottom right;
        box-shadow: 0 5px 30px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        z-index: 9999;
        font-family: 'Poppins', sans-serif;
    }

    body.show-chatbot .chatbot {
        opacity: 1;
        pointer-events: auto;
        transform: scale(1);
    }

    /* Header */
    .chatbot header {
        padding: 16px 20px;
        position: relative;
        text-align: center;
        color: #fff;
        background: linear-gradient(135deg, #1B5E20, #43A047);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .chatbot header span {
        position: absolute;
        right: 15px;
        top: 50%;
        display: none;
        cursor: pointer;
        transform: translateY(-50%);
    }

    .chatbot header h2 {
        font-size: 1.2rem;
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    /* Chatbox Area */
    .chatbot .chatbox {
        overflow-y: auto;
        height: 380px;
        padding: 20px 20px 70px;
        background: #f8f9fa;
    }

    .chatbot .chatbox::-webkit-scrollbar {
        width: 6px;
    }

    .chatbot .chatbox::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 3px;
    }

    .chatbox .chat {
        display: flex;
        list-style: none;
        margin-bottom: 15px;
    }

    .chatbox .incoming span {
        width: 32px;
        height: 32px;
        color: #fff;
        background: #1B5E20;
        text-align: center;
        line-height: 32px;
        border-radius: 4px;
        margin: 0 10px 7px 0;
        align-self: flex-end;
        font-size: 14px;
    }

    .chatbox .chat p {
        white-space: pre-wrap;
        padding: 12px 16px;
        border-radius: 10px 10px 10px 0;
        max-width: 75%;
        color: #fff;
        font-size: 0.95rem;
        background: #1B5E20;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        margin-bottom: 0;
    }

    .chatbox .incoming p {
        border-radius: 10px 10px 0 10px;
        color: #333;
        background: #fff;
        border: 1px solid #eee;
    }

    .chatbox .chat p.error {
        color: #721c24;
        background: #f8d7da;
    }

    /* Typical Typing Animation */
    .typing-animation {
        display: inline-flex;
        gap: 5px;
        padding: 5px 0;
    }

    .typing-animation .dot {
        height: 7px;
        width: 7px;
        border-radius: 50%;
        background-color: #aaa;
        animation: typing 1.5s infinite ease-in-out;
    }

    .typing-animation .dot:nth-child(1) {
        animation-delay: 0s;
    }

    .typing-animation .dot:nth-child(2) {
        animation-delay: 0.2s;
    }

    .typing-animation .dot:nth-child(3) {
        animation-delay: 0.4s;
    }

    @keyframes typing {
        0% {
            transform: translateY(0px);
            opacity: 0.5;
        }

        50% {
            transform: translateY(-5px);
            opacity: 1;
        }

        100% {
            transform: translateY(0px);
            opacity: 0.5;
        }
    }

    /* Input Area */
    .chatbot .chat-input {
        display: flex;
        gap: 5px;
        position: absolute;
        bottom: 0;
        width: 100%;
        background: #fff;
        padding: 10px 20px;
        border-top: 1px solid #eee;
    }

    .chat-input textarea {
        height: 55px;
        width: 100%;
        border: none;
        outline: none;
        resize: none;
        max-height: 180px;
        padding: 15px 15px 15px 0;
        font-size: 0.95rem;
        font-family: 'Poppins', sans-serif;
    }

    .chat-input span {
        align-self: flex-end;
        color: #1B5E20;
        cursor: pointer;
        height: 55px;
        display: flex;
        align-items: center;
        visibility: hidden;
        font-size: 1.35rem;
        transition: color 0.3s;
    }

    .chat-input textarea:valid~span {
        visibility: visible;
    }

    .chat-input span:hover {
        color: #43A047;
    }

    /* Small Screen Response */
    @media (max-width: 490px) {
        .chatbot-toggler {
            right: 20px;
            bottom: 20px;
        }

        .chatbot {
            right: 0;
            bottom: 0;
            height: 100%;
            border-radius: 0;
            width: 100%;
        }

        .chatbot .chatbox {
            height: 90%;
            padding: 25px 15px 100px;
        }

        .chatbot .chat-input {
            padding: 5px 15px;
        }

        .chatbot header span {
            display: block;
        }
    }

    /* Quick Actions */
    .quick-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-top: 10px;
    }

    .quick-btn {
        background: #fff;
        border: 1px solid #1B5E20;
        color: #1B5E20;
        border-radius: 20px;
        padding: 5px 12px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .quick-btn:hover {
        background: #1B5E20;
        color: #fff;
    }
</style>

<button class="chatbot-toggler">
    <span class="fas fa-comment-dots fs-3"></span>
    <span class="fas fa-times fs-3"></span>
</button>

<div class="chatbot">
    <header>
        <h2><i class="fas fa-robot"></i> EthioBot AI</h2>
        <span class="close-btn fas fa-times fs-4"></span>
    </header>
    <ul class="chatbox">
        <li class="chat incoming">
            <span class="fas fa-robot"></span>
            <div>
                <p>Hi there! 👋 I'm EthioBot, your AI assistant. How can I help you today?</p>
                <div class="quick-actions">
                    <button class="quick-btn" onclick="sendQuickMsg('Find a Hotel')">🏨 Hotels</button>
                    <button class="quick-btn" onclick="sendQuickMsg('Order Food')">🍔 Food</button>
                    <button class="quick-btn" onclick="sendQuickMsg('Real Estate')">🏠 Real Estate</button>
                    <button class="quick-btn" onclick="sendQuickMsg('Book Bus')">🚌 Bus</button>
                    <button class="quick-btn" onclick="sendQuickMsg('Book Taxi')">🚖 Taxi</button>
                    <button class="quick-btn" onclick="sendQuickMsg('Education')">🎓 Education</button>
                    <button class="quick-btn" onclick="sendQuickMsg('Find Broker')">💼 Broker</button>
                    <button class="quick-btn" onclick="sendQuickMsg('My Orders')">📦 Orders</button>
                </div>
            </div>
        </li>
    </ul>
    <div class="chat-input">
        <textarea placeholder="Enter a message..." spellcheck="false" required></textarea>
        <span id="send-btn" class="fas fa-paper-plane"></span>
    </div>
</div>

<script>
    const chatbotToggler = document.querySelector(".chatbot-toggler");
    const closeBtn = document.querySelector(".close-btn");
    const chatbox = document.querySelector(".chatbox");
    const chatInput = document.querySelector(".chat-input textarea");
    const sendChatBtn = document.querySelector(".chat-input span");

    let userMessage = null; // Variable to store user's message
    const inputInitHeight = chatInput.scrollHeight;

    // Knowledge Base (Predefined Intelligent Responses)
    // In a real production app, this would call a PHP API connected to OpenAI/Gemini
    const knowledgeBase = [
        {
            keywords: ['hotel', 'room', 'stay', 'booking', 'reservation'],
            response: "You can book premium hotels, rooms, and halls directly through our platform. Would you like to see available hotels?",
            link: "<?php echo BASE_URL; ?>/customer/booking.php",
            linkText: "Browse Hotels"
        },
        {
            keywords: ['food', 'restaurant', 'eat', 'dinner', 'lunch', 'breakfast', 'hungry', 'burger', 'pizza'],
            response: "Hungry? We have the best delivery service from top restaurants in Addis! Check out our menus.",
            link: "<?php echo BASE_URL; ?>/customer/index.php#hotels",
            linkText: "Order Food"
        },
        {
            keywords: ['house', 'apartment', 'villa', 'rent', 'real estate', 'buy', 'land', 'home'],
            response: "Looking for a dream home? Our Real Estate module features verified listings for rent and sale.",
            link: "<?php echo BASE_URL; ?>/realestate/index.php",
            linkText: "Go to Real Estate"
        },
        {
            keywords: ['bus', 'ticket', 'transport', 'travel', 'ride', 'coach'],
            response: "Need to travel? You can book bus tickets securely across Ethiopia using our Transport Bookings.",
            link: "<?php echo BASE_URL; ?>/customer/buses.php",
            linkText: "Book Bus Ticket"
        },
        {
            keywords: ['taxi', 'cab', 'driver', 'uber', 'ride'],
            response: "Need a ride? Our Taxi service connects you with verified drivers. You can also register a taxi company!",
            link: "<?php echo BASE_URL; ?>/customer/taxi.php",
            linkText: "Find Taxi"
        },
        {
            keywords: ['education', 'school', 'learn', 'grade', 'book', 'student'],
            response: "Access thousands of educational resources, textbooks, and exams for Grades 1-12.",
            link: "<?php echo BASE_URL; ?>/customer/education.php",
            linkText: "Go to Education"
        },
        {
            keywords: ['order', 'track', 'status', 'delivery'],
            response: "You can track your active orders and delivery status in real-time.",
            link: "<?php echo BASE_URL; ?>/customer/track_order.php",
            linkText: "Track Order"
        },
        {
            keywords: ['hello', 'hi', 'hey', 'greeting', 'start'],
            response: "Hello! Welcome to EthioServe. I can help you find services, book hotels, or answer questions. What do you need?"
        },
        {
            keywords: ['contact', 'support', 'phone', 'email', 'help'],
            response: "You can reach our support team at +251 911 234 567 or email support@ethioserve.com."
        },
        {
            keywords: ['broker', 'agent', 'sell', 'commission'],
            response: "Are you a broker? You can join us to list houses, cars, and more for a commission.",
            link: "<?php echo BASE_URL; ?>/broker/dashboard.php",
            linkText: "Broker Dashboard"
        }
    ];

    const createChatLi = (message, className) => {
        // Create a chat <li> element with passed message and className
        const chatLi = document.createElement("li");
        chatLi.classList.add("chat", className);

        let content = className === "outgoing"
            ? `<p></p>`
            : `<span class="fas fa-robot"></span><div><p></p></div>`;

        chatLi.innerHTML = content;
        chatLi.querySelector("p").textContent = message;
        return chatLi;
    }

    const generateResponse = (userMsg) => {
        const msg = indentifyIntent(userMsg.toLowerCase());

        setTimeout(() => {
            const incomingChatLi = createChatLi(msg.response, "incoming");

            // Add link button if exists
            if (msg.link) {
                const btnDiv = document.createElement("div");
                btnDiv.style.marginTop = "10px";
                btnDiv.innerHTML = `<a href="${msg.link}" class="btn btn-sm btn-outline-success rounded-pill">${msg.linkText}</a>`;
                incomingChatLi.querySelector("div").appendChild(btnDiv);
            }

            chatbox.appendChild(incomingChatLi);
            chatbox.scrollTo(0, chatbox.scrollHeight);
        }, 600);
    }

    const indentifyIntent = (msg) => {
        // Simple NLP (Natural Language Processing) simulation
        for (const topic of knowledgeBase) {
            if (topic.keywords.some(v => msg.includes(v))) {
                return topic;
            }
        }
        // Default response
        return {
            response: "I'm not sure I understand. I can help with Hotels, Real Estate, Transport, or Food Delivery. Try asking about one of those!"
        };
    }

    const handleChat = () => {
        userMessage = chatInput.value.trim();
        if (!userMessage) return;

        // Clear input area and reset height
        chatInput.value = "";
        chatInput.style.height = `${inputInitHeight}px`;

        // Append user's message
        chatbox.appendChild(createChatLi(userMessage, "outgoing"));
        chatbox.scrollTo(0, chatbox.scrollHeight);

        // Show typing animation
        setTimeout(() => {
            const typingLi = document.createElement("li");
            typingLi.classList.add("chat", "incoming", "typing");
            typingLi.innerHTML = `<span class="fas fa-robot"></span><div class="typing-animation"><div class="dot"></div><div class="dot"></div><div class="dot"></div></div>`;
            chatbox.appendChild(typingLi);
            chatbox.scrollTo(0, chatbox.scrollHeight);

            // Remove typing and show response
            setTimeout(() => {
                chatbox.removeChild(typingLi);
                generateResponse(userMessage);
            }, 1000);
        }, 600);
    }

    // Quick Message Function
    window.sendQuickMsg = (msg) => {
        chatInput.value = msg;
        handleChat();
    }

    chatInput.addEventListener("input", () => {
        // Auto-resize input
        chatInput.style.height = `${inputInitHeight}px`;
        chatInput.style.height = `${chatInput.scrollHeight}px`;
    });

    chatInput.addEventListener("keydown", (e) => {
        // Send on Enter (shift+enter for new line)
        if (e.key === "Enter" && !e.shiftKey && window.innerWidth > 800) {
            e.preventDefault();
            handleChat();
        }
    });

    sendChatBtn.addEventListener("click", handleChat);
    closeBtn.addEventListener("click", () => document.body.classList.remove("show-chatbot"));
    chatbotToggler.addEventListener("click", () => document.body.classList.toggle("show-chatbot"));
</script>