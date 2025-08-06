import React, { useState, useRef } from 'react';
import { View, StyleSheet } from 'react-native';
import { Button, Text } from 'react-native-paper';
import { Audio } from 'expo-av';
import * as DocumentPicker from 'expo-document-picker';
import { StackScreenProps } from '@react-navigation/stack';
import { RootStackParamList } from '../../App';

interface Props extends StackScreenProps<RootStackParamList, 'Record'> {}

const RecordScreen: React.FC<Props> = ({ navigation }) => {
  const [recording, setRecording] = useState<Audio.Recording | null>(null);
  const [recordedUri, setRecordedUri] = useState<string | null>(null);

  const startRecording = async () => {
    try {
      console.log('Requesting permissions..');
      const permission = await Audio.requestPermissionsAsync();
      if (permission.status !== 'granted') {
        alert('Permission to access microphone is required!');
        return;
      }
      console.log('Starting recording..');
      await Audio.setAudioModeAsync({
        allowsRecordingIOS: true,
        playsInSilentModeIOS: true,
      });
      const { recording } = await Audio.Recording.createAsync(
        Audio.RecordingOptionsPresets.HIGH_QUALITY
      );
      setRecording(recording);
      console.log('Recording started');
    } catch (err) {
      console.error('Failed to start recording', err);
    }
  };

  const stopRecording = async () => {
    console.log('Stopping recording..');
    if (!recording) return;
    await recording.stopAndUnloadAsync();
    const uri = recording.getURI();
    setRecording(null);
    if (uri) {
      setRecordedUri(uri);
    }
  };

  const pickAudio = async () => {
    const result = await DocumentPicker.getDocumentAsync({ type: 'audio/*' });
    if (result.type === 'success') {
      setRecordedUri(result.uri);
    }
  };

  return (
    <View style={styles.container}>
      <Text variant="bodyMedium" style={{ marginBottom: 16 }}>
        Record a sample voice or upload an existing audio file (WAV/MP3)
      </Text>
      {recording ? (
        <Button icon="stop" mode="contained" onPress={stopRecording}>
          Stop Recording
        </Button>
      ) : (
        <Button icon="microphone" mode="contained" onPress={startRecording}>
          Start Recording
        </Button>
      )}
      <Button style={{ marginTop: 16 }} mode="outlined" onPress={pickAudio}>
        Upload Audio File
      </Button>

      {recordedUri && (
        <View style={{ marginTop: 24 }}>
          <Text>Selected Audio:</Text>
          <Text numberOfLines={1}>{recordedUri}</Text>
          <Button style={{ marginTop: 16 }} mode="contained" onPress={() => navigation.navigate('Synthesis', { recordedUri })}>
            Continue to Generate Voice
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
});

export default RecordScreen;