import Chat from "./Chat";

function App() {
  return (
    <div style={appStyles.page}>
      <Chat />
    </div>
  );
}

export default App;

const appStyles = {
  page: {
    minHeight: "100vh",
    display: "flex",
    justifyContent: "center",
    alignItems: "center",
    background: "#f3f4f6" // gris clair (optionnel)
  }
};
