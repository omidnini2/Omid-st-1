import React, { useEffect, useState } from 'react';
import { View, StyleSheet } from 'react-native';
import { Button, Text, TextInput } from 'react-native-paper';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Audio } from 'expo-av';
import * as FileSystem from 'expo-file-system';
import * as Sharing from 'expo-sharing';
import { StackScreenProps } from '@react-navigation/stack';
import { RootStackParamList } from '../../App';

interface Props extends StackScreenProps<RootStackParamList, 'Synthesis'> {}

const SynthesisScreen: React.FC<Props> = ({ route }) => {
  const [text, setText] = useState('Hello world!');
  const [audioUri, setAudioUri] = useState<string | null>(null);
  const [isGenerating, setIsGenerating] = useState(false);
  const [sound, setSound] = useState<Audio.Sound | null>(null);

  const generateSpeech = async () => {
    try {
      const key = await AsyncStorage.getItem('azure_key');
      const region = await AsyncStorage.getItem('azure_region');
      const voiceName = await AsyncStorage.getItem('azure_voice');
      if (!key || !region || !voiceName) {
        alert('Missing Azure credentials');
        return;
      }

      setIsGenerating(true);

      const endpoint = `https://${region}.tts.speech.microsoft.com/cognitiveservices/v1`;
      const ssml = `<?xml version="1.0" encoding="utf-8"?><speak version="1.0" xml:lang="en-US"><voice name="${voiceName}">${text}</voice></speak>`;

      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/ssml+xml',
          'Ocp-Apim-Subscription-Key': key,
          'X-Microsoft-OutputFormat': 'audio-16khz-128kbitrate-mono-mp3',
        },
        body: ssml,
      });

      if (!response.ok) {
        const err = await response.text();
        throw new Error(err);
      }

      const arrayBuffer = await response.arrayBuffer();
      const base64Audio = arrayBufferToBase64(arrayBuffer);
      const path = FileSystem.documentDirectory + `speech-${Date.now()}.mp3`;
      await FileSystem.writeAsStringAsync(path, base64Audio, {
        encoding: FileSystem.EncodingType.Base64,
      });

      setAudioUri(path);
      setIsGenerating(false);
      alert('Speech generated!');
    } catch (e: any) {
      console.error(e);
      alert('Failed to generate speech: ' + e.message);
      setIsGenerating(false);
    }
  };

  const playAudio = async () => {
    if (!audioUri) return;
    if (sound) {
      await sound.replayAsync();
      return;
    }
    const { sound: newSound } = await Audio.Sound.createAsync({ uri: audioUri });
    setSound(newSound);
    await newSound.playAsync();
  };

  const downloadAudio = async () => {
    if (audioUri && (await Sharing.isAvailableAsync())) {
      await Sharing.shareAsync(audioUri);
    } else {
      alert('Sharing not available on this device');
    }
  };

  const arrayBufferToBase64 = (buffer: ArrayBuffer): string => {
    let binary = '';
    const bytes = new Uint8Array(buffer);
    const len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
      binary += String.fromCharCode(bytes[i]);
    }
    // @ts-ignore
    return btoa(binary);
  };

  return (
    <View style={styles.container}>
      <TextInput
        label="Text to Synthesize"
        value={text}
        onChangeText={setText}
        multiline
        style={styles.input}
      />
      <Button mode="contained" onPress={generateSpeech} loading={isGenerating} disabled={!text.length}>
        Generate Speech
      </Button>

      {audioUri && (
        <View style={{ marginTop: 24 }}>
          <Button mode="contained" onPress={playAudio}>
            Play
          </Button>
          <Button style={{ marginTop: 12 }} mode="outlined" onPress={downloadAudio}>
            Download
          </Button>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 24,
  },
  input: {
    marginBottom: 20,
  },
});

export default SynthesisScreen;