# Voice Clone Azure App (Expo / React Native)

This project is a simple **free** Android application (can be built as APK) that:

1. Lets you save your own Azure Speech credentials (Free tier F0 is enough).
2. Lets you record or upload a voice sample (placeholder for Custom Neural Voice training).
3. Converts any input text into speech using your custom voice (or any Azure voice) and plays / downloads the result.
4. Supports Dark / Light theme.

> NOTE: Real-time voice-cloning requires that you have **already** created and deployed a *Custom Neural Voice* in Azure Speech Studio. You must supply the **voice name** (e.g. `my-voice-neural`) in the credentials screen.

## Quick start

```bash
# 1. Install dependencies
npm install # or yarn install

# 2. Start Expo dev server
npm start # then press 'a' to run on Android emulator / device
```

## Building an APK (no Play-Store signing)

```bash
npx expo export --platform android
# or
npx eas build -p android --profile preview
```

The unsigned APK/AAB will appear in the generated `dist/` folder or on the EAS build portal.

## Environment / Storage

The app stores the following values in *AsyncStorage* on the device:

- `azure_key` – Subscription key
- `azure_region` – Region (e.g. `eastus`)
- `azure_voice` – Voice short name (e.g. `en-US-JennyNeural` or your custom one)
- `theme` – `light` or `dark`

No data is sent anywhere except directly to Azure endpoints you provide.

---

Made with ❤️ and React Native Paper.