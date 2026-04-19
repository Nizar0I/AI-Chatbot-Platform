import { useEffect, useRef, useState } from "react";

const API_URL = "http://127.0.0.1/chatbot-backend/public/api/chat";

export default function Chat() {
  const [message, setMessage] = useState("");
  const [loading, setLoading] = useState(false);

  // ✅ NEW: options (boutons)
  const [options, setOptions] = useState([]);

  const [messages, setMessages] = useState([
    { sender: "bot", text: "Bonjour 👋 Comment puis-je t'aider ?" },
  ]);

  const chatEndRef = useRef(null);

  useEffect(() => {
    chatEndRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages, options, loading]);

  // ✅ UPDATED: accepte un texte custom (bouton ou input)
  const sendMessage = async (customText) => {
    const textToSend = (customText ?? message).trim();
    if (!textToSend || loading) return;

    setLoading(true);

    // Ajouter message utilisateur
    setMessages((prev) => [...prev, { sender: "user", text: textToSend }]);
    setMessage("");

    // ✅ Quand on envoie un message, on cache les anciens boutons
    setOptions([]);

    try {
      const res = await fetch(API_URL, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Accept: "application/json",
        },
        body: JSON.stringify({ message: textToSend }),
      });

      const data = await res.json();

      // Ajouter réponse bot
      setMessages((prev) => [...prev, { sender: "bot", text: data.reply }]);

      // ✅ NEW: options du bot (boutons)
      setOptions(Array.isArray(data.options) ? data.options : []);
    } catch {
      setMessages((prev) => [
        ...prev,
        { sender: "bot", text: "❌ Erreur serveur" },
      ]);
      setOptions([]);
    } finally {
      setLoading(false);
    }
  };

  const handleKeyDown = (e) => {
    if (e.key === "Enter") sendMessage();
  };

  const handleOptionClick = (opt) => {
    // Envoie direct le texte du bouton
    sendMessage(opt);
  };

  return (
    <div style={styles.container}>
      <div style={styles.chatWrapper}>
        {/* HEADER */}
        <div style={styles.header}>🤖 Mini Chatbot</div>

        {/* MESSAGES */}
        <div style={styles.chatBox}>
          {messages.map((msg, i) => (
            <div
              key={i}
              style={{
                ...styles.message,
                alignSelf: msg.sender === "user" ? "flex-end" : "flex-start",
                background: msg.sender === "user" ? "#2563eb" : "#e5e7eb",
                color: msg.sender === "user" ? "white" : "black",
              }}
            >
              {msg.text}
            </div>
          ))}

          {/* LOADING */}
          {loading && (
            <div
              style={{
                ...styles.message,
                alignSelf: "flex-start",
                opacity: 0.6,
                background: "#e5e7eb",
                color: "black",
              }}
            >
              🤖 Le bot écrit...
            </div>
          )}

          {/* ✅ OPTIONS (boutons cliquables) */}
          {!loading && options.length > 0 && (
            <div style={styles.optionsRow}>
              {options.map((opt, idx) => (
                <button
                  key={idx}
                  style={styles.optionBtn}
                  onClick={() => handleOptionClick(opt)}
                >
                  {opt}
                </button>
              ))}
            </div>
          )}

          <div ref={chatEndRef} />
        </div>

        {/* INPUT */}
        <div style={styles.inputBox}>
          <input
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            onKeyDown={handleKeyDown}
            placeholder="Écris ton message..."
            style={styles.input}
            disabled={loading}
          />
          <button
            onClick={() => sendMessage()}
            style={{
              ...styles.button,
              opacity: loading ? 0.7 : 1,
              cursor: loading ? "not-allowed" : "pointer",
            }}
            disabled={loading}
          >
            Envoyer
          </button>
        </div>
      </div>
    </div>
  );
}

const styles = {
  header: {
    padding: "14px",
    textAlign: "center",
    fontWeight: "bold",
    fontSize: "18px",
    borderBottom: "1px solid #eee",
    background: "#2563eb",
    color: "white",
  },
  container: {
    width: "100vw",
    height: "100vh",
    display: "flex",
    justifyContent: "center",
    alignItems: "center",
    background: "linear-gradient(135deg, #1e3a8a, #2563eb, #38bdf8)",
  },
  chatBox: {
    flex: 1,
    padding: "16px",
    display: "flex",
    flexDirection: "column",
    gap: "10px",
    overflowY: "auto",
    background: "#f9fafb",
  },
  message: {
    padding: "10px 14px",
    borderRadius: "12px",
    maxWidth: "75%",
    lineHeight: 1.4,
    wordBreak: "break-word",
  },

  // ✅ NEW styles
  optionsRow: {
    display: "flex",
    flexWrap: "wrap",
    gap: "8px",
    marginTop: "6px",
    paddingTop: "8px",
    borderTop: "1px dashed #e5e7eb",
  },
  optionBtn: {
    border: "1px solid #2563eb",
    background: "white",
    color: "#2563eb",
    padding: "8px 10px",
    borderRadius: "999px",
    cursor: "pointer",
    fontSize: "13px",
    fontWeight: "600",
  },

  inputBox: {
    display: "flex",
    padding: "10px",
    gap: "10px",
    borderTop: "1px solid #ddd",
    background: "white",
  },
  input: {
    flex: 1,
    padding: "10px",
    borderRadius: "8px",
    border: "1px solid #ccc",
    outline: "none",
  },
  button: {
    padding: "10px 16px",
    borderRadius: "8px",
    border: "none",
    background: "#2563eb",
    color: "white",
    fontWeight: "bold",
  },
  chatWrapper: {
    width: "380px",
    height: "520px",
    background: "white",
    borderRadius: "18px",
    boxShadow: "0 20px 40px rgba(0,0,0,0.15)",
    display: "flex",
    flexDirection: "column",
    overflow: "hidden",
    backdropFilter: "blur(10px)",
  },
};

// export default function Chat() {
  
//   const [message, setMessage] = useState("");
//   const [loading, setLoading] = useState(false);

//   const [messages, setMessages] = useState([
//     { sender: "bot", text: "Bonjour 👋 Comment puis-je t'aider ?" },
//   ]);

//   const chatEndRef = useRef(null);

//   useEffect(() => {
//     chatEndRef.current?.scrollIntoView({ behavior: "smooth" });
//   }, [messages]);
// const sendMessage = async () => {
//   if (!message.trim() || loading) return;

//   setLoading(true);

//   setMessages(prev => [...prev, { sender: "user", text: message }]);
//   setMessage("");

//   try {
//     const res = await fetch(
//       "http://127.0.0.1/chatbot-backend/public/api/chat",
//       {
//         method: "POST",
//         headers: {
//           "Content-Type": "application/json",
//           "Accept": "application/json"
//         },
//         body: JSON.stringify({ message })
//       }
//     );

//     const data = await res.json();

//     setMessages(prev => [
//       ...prev,
//       { sender: "bot", text: data.reply }
//     ]);
//   } catch {
//   setMessages(prev => [
//     ...prev,
//     { sender: "bot", text: "❌ Erreur serveur" }
//   ]);
// }
//  finally {
//     setLoading(false);
//   }
//  };



//   const handleKeyDown = (e) => {
//   if (e.key === "Enter") {
//     sendMessage();
//   }
// };


//   return (
//   <div style={styles.container}>
//     <div style={styles.chatWrapper}>

//       {/* HEADER */}
//       <div style={styles.header}>
//         🤖 Mini Chatbot
//       </div>

//       {/* MESSAGES */}
//       <div style={styles.chatBox}>
//         {messages.map((msg, i) => (
//           <div
//             key={i}
//             style={{
//               ...styles.message,
//               alignSelf: msg.sender === "user" ? "flex-end" : "flex-start",
//               background: msg.sender === "user" ? "#2563eb" : "#e5e7eb",
//               color: msg.sender === "user" ? "white" : "black"
//             }}
//           >
//             {msg.text}
//           </div>
//         ))}
//         {loading && (
//         <div
//       style={{
//       ...styles.message,
//       alignSelf: "flex-start",
//       opacity: 0.6,
//       background: "#e5e7eb",
//       color: "black"
//          }}
//           >
//     🤖 Le bot écrit...
//   </div>
// )}

//       </div>

//       {/* INPUT */}
//       <div style={styles.inputBox}>
//         <input
//   value={message}
//   onChange={e => setMessage(e.target.value)}
//   onKeyDown={handleKeyDown}
//   placeholder="Écris ton message..."
//   style={styles.input}
// />
//         <button onClick={sendMessage} style={styles.button}>
//           Envoyer
//         </button>
//       </div>

//     </div>
//   </div>
// );
// }

// const styles = {
//   header: {
//   padding: "14px",
//   textAlign: "center",
//   fontWeight: "bold",
//   fontSize: "18px",
//   borderBottom: "1px solid #eee",
//   background: "#2563eb",
//   color: "white"
// },
//   container: {
//   width: "100vw",
//   height: "100vh",
//   display: "flex",
//   justifyContent: "center",
//   alignItems: "center",
//   background: "linear-gradient(135deg, #1e3a8a, #2563eb, #38bdf8)",
// },

//  chatBox: {
//   flex: 1,
//   padding: "16px",
//   display: "flex",
//   flexDirection: "column",
//   gap: "10px",
//   overflowY: "auto",
//   background: "#f9fafb"
// },
//   message: {
//     padding: "10px 14px",
//     borderRadius: "12px",
//     maxWidth: "75%",
//     lineHeight: 1.4,
//     wordBreak: "break-word",
//   },
//   inputBox: {
//     display: "flex",
//     padding: "10px",
//     gap: "10px",
//     borderTop: "1px solid #ddd",
//     background: "white",
//   },
//   input: {
//     flex: 1,
//     padding: "10px",
//     borderRadius: "8px",
//     border: "1px solid #ccc",
//     outline: "none",
//   },
//   button: {
//     padding: "10px 16px",
//   borderRadius: "8px",
//   border: "none",
//   background: "#2563eb",
//   color: "white",
//   cursor: "pointer",
//   fontWeight: "bold"
//   },
//   chatWrapper: {
//   width: "380px",
//   height: "520px",
//    background: "white",
//   borderRadius: "18px",
//   boxShadow: "0 20px 40px rgba(0,0,0,0.15)",
//   display: "flex",
//   flexDirection: "column",
//   overflow: "hidden",  
//   backdropFilter: "blur(10px)",
// },
 
// };
