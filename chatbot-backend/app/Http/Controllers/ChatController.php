<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Appointment;
use Carbon\Carbon;



class ChatController extends Controller
{

   public function send(Request $request)
{
    $request->validate([
        'message' => 'required|string',
        'conversation_id' => 'nullable|integer',
    ]);

    // 1️⃣ Conversation
    $conversation = $request->conversation_id
        ? Conversation::find($request->conversation_id)
        : Conversation::create();

    if (!$conversation) {
        $conversation = Conversation::create();
    }

    // ✅ NEW: init step + data (pour garder l’état)
    $conversation->step = $conversation->step ?? 'main_menu';
    $conversation->data = $conversation->data ?? [];
    $conversation->save();

    // 2️⃣ Enregistrer message utilisateur
    Message::create([
        'conversation_id' => $conversation->id,
        'sender' => 'user',
        'content' => $request->message,
    ]);

    // 3️⃣ ✅ UPDATED: botReply reçoit aussi la conversation
    $bot = $this->botReply($request->message, $conversation);

    // 4️⃣ Enregistrer message bot
    Message::create([
        'conversation_id' => $conversation->id,
        'sender' => 'bot',
        'content' => $bot['reply'],
    ]);

    // 5️⃣ Réponse API vers frontend
    return response()->json([
        'conversation_id' => $conversation->id,
        'reply' => $bot['reply'],
        'options' => $bot['options'] ?? [],
        'step' => $bot['step'] ?? null,
    ]);
}


private function botReply(string $text, Conversation $conversation): array
{
    $text = trim($text);

    // 1) MENU
   if (
    str_contains(mb_strtolower($text), 'menu') ||
    in_array(mb_strtolower($text), ['start', 'bonjour', 'salut'])
      ) {
        $conversation->step = 'main_menu';
        $conversation->data = [];
        $conversation->save();

        return [
            'reply' => "Bonjour 👋 Bienvenue au cabinet dentaire. Que souhaitez-vous faire ?",
            'options' => [
                "📅 Prendre un rendez-vous",
                "🦷 Nos services",
                "🚨 Urgence dentaire",
                "💰 Tarifs",
                "📍 Adresse & horaires",
            ],
        ];
    }

    // 2) START RDV
    if (str_contains(mb_strtolower($text), 'rendez')) {
        $conversation->step = 'choose_date';
        $conversation->data = [];
        $conversation->save();

        return [
            'reply' => "Très bien ✅ Choisissez une date :",
            'options' => ["📅 Aujourd’hui", "📅 Demain", "📅 Cette semaine", "⬅️ Retour au menu"],
        ];
    }

    // 3) DATE -> HEURE
    if ($conversation->step === 'choose_date') {
        if ($text === "⬅️ Retour au menu") return $this->botReply("menu", $conversation);

        $conversation->data['date'] = $text;
        $conversation->step = 'choose_time';
        $conversation->save();

        return [
            'reply' => "Parfait ✅ Choisissez une heure :",
            'options' => ["09:00", "10:30", "12:00", "15:00", "17:30", "⬅️ Retour au menu"],
        ];
    }

    // 4) HEURE -> NOM
    if ($conversation->step === 'choose_time') {
        if ($text === "⬅️ Retour au menu") return $this->botReply("menu", $conversation);

        $conversation->data['time'] = $text;
        $conversation->step = 'ask_name';
        $conversation->save();

        return [
            'reply' => "✅ Très bien. Quel est votre nom complet ?",
            'options' => [],
        ];
    }

    // 5) NOM -> TELEPHONE
    if ($conversation->step === 'ask_name') {
        $conversation->data['name'] = $text;
        $conversation->step = 'ask_phone';
        $conversation->save();

        return [
            'reply' => "Merci 😊 Quel est votre numéro de téléphone ?",
            'options' => [],
        ];
    }

    // 6) TELEPHONE -> CONFIRMATION
    if ($conversation->step === 'ask_phone') {
        $conversation->data['phone'] = $text;
        $conversation->step = 'confirm_rdv';
        $conversation->save();

        $date = $conversation->data['date'] ?? '';
        $time = $conversation->data['time'] ?? '';
        $name = $conversation->data['name'] ?? '';

        return [
            'reply' => "📌 Confirmez votre rendez-vous :\n👤 $name\n📅 $date\n🕒 $time\n✅ Confirmer ?",
            'options' => ["✅ Confirmer", "❌ Annuler", "⬅️ Retour au menu"],
        ];
    }

    // 7) CONFIRMATION
   if ($conversation->step === 'confirm_rdv') {

    if ($text === "⬅️ Retour au menu") {
        return $this->botReply("menu", $conversation);
    }

    if (str_contains($text, "Confirmer")) {

        $dateLabel = $conversation->data['date'] ?? null;   // "📅 Aujourd’hui" etc.
        $time      = $conversation->data['time'] ?? null;   // "10:30"
        $name      = $conversation->data['name'] ?? null;
        $phone     = $conversation->data['phone'] ?? null;
        $service   = $conversation->data['service'] ?? null;

        // ✅ convertir date label -> vraie date
        $date = $this->resolveDateLabel($dateLabel);

        if (!$date || !$time || !$name || !$phone) {
            return [
                'reply' => "❌ معلومات ناقصة. رجاءً أعد المحاولة من جديد (menu).",
                'options' => ["📋 Menu"],
                'step' => 'fallback',
            ];
        }

        // ✅ vérifier créneau déjà pris
        $exists = Appointment::where('date', $date)->where('time', $time)->exists();
        if ($exists) {
            $conversation->step = 'choose_time';
            $conversation->save();

            return [
                'reply' => "⛔ هذا الوقت محجوز بالفعل. اختر وقتًا آخر من فضلك:",
                'options' => ["09:00", "10:30", "12:00", "15:00", "17:30", "⬅️ Retour au menu"],
                'step' => 'choose_time',
            ];
        }

        // ✅ enregistrer RDV
        Appointment::create([
            'conversation_id' => $conversation->id,
            'patient_name'    => $name,
            'phone'           => $phone,
            'service'         => $service,
            'date'            => $date,
            'time'            => $time,
            'status'          => 'confirmed',
        ]);

        $conversation->step = 'done';
        $conversation->save();

        return [
            'reply' => "✅ Rendez-vous confirmé !\n👤 $name\n📞 $phone\n🦷 " . ($service ?? "Consultation") . "\n📅 $date\n🕒 $time\nÀ très bientôt 🦷✨",
            'options' => ["📋 Menu"],
            'step' => 'done',
        ];
    }

    // annuler
    $conversation->step = 'main_menu';
    $conversation->data = [];
    $conversation->save();

    return [
        'reply' => "D'accord, rendez-vous annulé ❌. Tapez 'menu' pour recommencer.",
        'options' => ["📋 Menu"],
        'step' => 'main_menu',
    ];
}


    // fallback
    return [
        'reply' => "Je n’ai pas compris 😅. Tapez 'menu' pour voir les options.",
        'options' => ["📋 Menu"],
    ];
}
private function resolveDateLabel(?string $label): ?string
{
    if (!$label) return null;

    $labelLower = mb_strtolower($label);

    // Aujourd’hui / Demain
    if (str_contains($labelLower, 'aujourd')) {
        return Carbon::today()->toDateString();
    }

    if (str_contains($labelLower, 'demain')) {
        return Carbon::tomorrow()->toDateString();
    }

    // "Cette semaine" -> on prend la date du prochain jour ouvrable (ex: demain)
    if (str_contains($labelLower, 'semaine')) {
        return Carbon::tomorrow()->toDateString();
    }

    // Si tu veux plus tard accepter "2026-01-20"
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $label)) {
        return $label;
    }

    return null;
}


    
    



    // public function send(Request $request)
    // {
    //     $request->validate([
    //         'message' => 'required|string',
    //         'conversation_id' => 'nullable|integer',
    //     ]);

    //     // Conversation
    //     $conversation = $request->conversation_id
    //         ? Conversation::find($request->conversation_id)
    //         : Conversation::create();

    //     if (!$conversation) {
    //         $conversation = Conversation::create();
    //     }

    //     // Message utilisateur
    //     Message::create([
    //         'conversation_id' => $conversation->id,
    //         'sender' => 'user',
    //         'content' => $request->message,
    //     ]);

    //     // Réponse simple du bot
    //     $reply = $this->botReply($request->message);

    //     // Message bot
    //     Message::create([
    //         'conversation_id' => $conversation->id,
    //         'sender' => 'bot',
    //         'content' => $reply,
    //     ]);

    //     return response()->json([
    //         'conversation_id' => $conversation->id,
    //         'reply' => $reply,
    //     ]);
    // }

//     private function botReply(string $text): string{
    
    

//     $rules = [
//         ['keywords' => ['bonjour', 'salut','hi','hello','hii','bonsoir'], 'reply' => "Bonjour 👋 Ravi de te voir ! Comment puis-je t’aider aujourd’hui ?"],
//         ['keywords' => ['ça va', 'cava'], 'reply' => "Je vais très bien 😊 Merci de demander ! Et toi ?"],
//         ['keywords' => ['cava merci'], 'reply' => "Ravi de l’entendre ! Que puis-je faire pour toi aujourd’hui ?"],
        
//         ['keywords' => ['qui es-tu', 'tu es qui'], 'reply' => "Je suis un mini chatbot IA 🤖 développé avec Laravel et React."],
//         ['keywords' => ['que fais-tu', 'tu fais quoi'], 'reply' => "Je peux répondre à des questions simples, discuter avec toi et enregistrer nos conversations 📚"],
//         ['keywords' => ['react'], 'reply' => "React est utilisé pour l’interface du chatbot ⚛️. C’est rapide et moderne."],
//         ['keywords' => ['laravel'], 'reply' => "Laravel gère le backend du chatbot 🧠 : API, base de données et logique."],
//         ['keywords' => ['merci'], 'reply' => "Avec plaisir 😊 Si tu as d’autres questions, je suis là !"],
//         ['keywords' => ['au revoir', 'bye'], 'reply' => "Au revoir 👋 À très bientôt !"],
//     ];

//     foreach ($rules as $rule) {
//         foreach ($rule['keywords'] as $keyword) {
//             if (str_contains($text, $keyword)) {
//                 return $rule['reply'];
//             }
//         }
//     }

//     return "🤖 Je n’ai pas encore compris, mais j’apprends chaque jour !";
// }

     
//     private function botReply(string $text): string
//     {

//     $text = strtolower($text);

//     if (str_contains($text, 'bonjour') || str_contains($text, 'salut')) {
//         return "Bonjour 👋 Ravi de te voir ! Comment puis-je t’aider aujourd’hui ?";
//     }

//     if (str_contains($text, 'ça va') || str_contains($text, 'cava')) {
//         return "Je vais très bien 😊 Merci de demander ! Et toi ?";
//     }

//     if (str_contains($text, 'qui es-tu') || str_contains($text, 'tu es qui')) {
//         return "Je suis un mini chatbot IA 🤖 développé avec Laravel et React.";
//     }

//     if (str_contains($text, 'que fais-tu') || str_contains($text, 'tu fais quoi')) {
//         return "Je peux répondre à des questions simples, discuter avec toi et enregistrer nos conversations 📚";
//     }

//     if (str_contains($text, 'react')) {
//         return "React est utilisé pour l’interface du chatbot ⚛️. C’est rapide et moderne.";
//     }

//     if (str_contains($text, 'laravel')) {
//         return "Laravel gère le backend du chatbot 🧠 : API, base de données et logique.";
//     }

//     if (str_contains($text, 'merci')) {
//         return "Avec plaisir 😊 Si tu as d’autres questions, je suis là !";
//     }

//     if (str_contains($text, 'au revoir') || str_contains($text, 'bye')) {
//         return "Au revoir 👋 À très bientôt !";
//     }
    
//     return "🤖 Je n’ai pas encore compris, mais j’apprends chaque jour !";
    
// }

    // private function botReply(string $text): string
    // {
    //     $text = strtolower($text);

    //     if (str_contains($text, 'bonjour')) {
    //         return "Bonjour 👋 Comment puis-je t'aider ?";
    //     }

    //     if (str_contains($text, 'merci')) {
    //         return "Avec plaisir 😊";
    //     }

    //     return "Je suis un chatbot en développement 🤖";
    // }
}
