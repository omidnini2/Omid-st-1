import React, { useState, useEffect } from 'react';
import { View, StyleSheet } from 'react-native';
import { TextInput, Button, Text, Switch } from 'react-native-paper';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { StackScreenProps } from '@react-navigation/stack';
import { RootStackParamList } from '../../App';

interface Props extends StackScreenProps<RootStackParamList, 'Credentials'> {
  toggleTheme: () => void;
  isDark: boolean;
}

const CredentialsScreen: React.FC<Props> = ({ navigation, toggleTheme, isDark }) => {
  const [key, setKey] = useState('');
  const [region, setRegion] = useState('');
  const [voiceName, setVoiceName] = useState('');
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    // Load stored credentials
    (async () => {
      const storedKey = await AsyncStorage.getItem('azure_key');
      const storedRegion = await AsyncStorage.getItem('azure_region');
      const storedVoice = await AsyncStorage.getItem('azure_voice');
      if (storedKey) setKey(storedKey);
      if (storedRegion) setRegion(storedRegion);
      if (storedVoice) setVoiceName(storedVoice);
    })();
  }, []);

  const saveAndContinue = async () => {
    setLoading(true);
    await AsyncStorage.setItem('azure_key', key);
    await AsyncStorage.setItem('azure_region', region);
    await AsyncStorage.setItem('azure_voice', voiceName);
    setLoading(false);
    navigation.navigate('Record');
  };

  return (
    <View style={styles.container}>
      <TextInput label="Subscription Key" value={key} onChangeText={setKey} style={styles.input} secureTextEntry />
      <TextInput label="Region" value={region} onChangeText={setRegion} style={styles.input} />
      <TextInput label="Voice Name" value={voiceName} onChangeText={setVoiceName} style={styles.input} placeholder="e.g., en-US-JennyNeural" />

      <View style={styles.row}>
        <Text variant="bodyMedium">Dark Theme</Text>
        <Switch value={isDark} onValueChange={toggleTheme} />
      </View>

      <Button mode="contained" onPress={saveAndContinue} loading={loading} disabled={!key || !region || !voiceName}>
        Save & Continue
      </Button>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 24,
    gap: 12,
  },
  input: {
    marginBottom: 12,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginVertical: 16,
  },
});

export default CredentialsScreen;