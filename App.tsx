import React, { useMemo, useState, useEffect } from 'react';
import { NavigationContainer, DefaultTheme as NavLightTheme, DarkTheme as NavDarkTheme } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { Provider as PaperProvider, MD3DarkTheme, MD3LightTheme } from 'react-native-paper';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { StatusBar } from 'expo-status-bar';
import CredentialsScreen from './src/screens/CredentialsScreen';
import RecordScreen from './src/screens/RecordScreen';
import SynthesisScreen from './src/screens/SynthesisScreen';

const Stack = createStackNavigator();

export type RootStackParamList = {
  Credentials: undefined;
  Record: undefined;
  Synthesis: { recordedUri?: string };
};

export default function App() {
  const [isDark, setIsDark] = useState(false);

  // Load theme preference
  useEffect(() => {
    (async () => {
      const pref = await AsyncStorage.getItem('theme');
      if (pref) setIsDark(pref === 'dark');
    })();
  }, []);

  const toggleTheme = async () => {
    const newValue = !isDark;
    setIsDark(newValue);
    await AsyncStorage.setItem('theme', newValue ? 'dark' : 'light');
  };

  const paperTheme = useMemo(() => (isDark ? MD3DarkTheme : MD3LightTheme), [isDark]);
  const navTheme = isDark ? NavDarkTheme : NavLightTheme;

  return (
    <PaperProvider theme={paperTheme}>
      <NavigationContainer theme={navTheme}>
        <StatusBar style={isDark ? 'light' : 'dark'} />
        <Stack.Navigator>
          <Stack.Screen name="Credentials" options={{ title: 'Azure Credentials' }}>
            {props => <CredentialsScreen {...props} toggleTheme={toggleTheme} isDark={isDark} />}
          </Stack.Screen>
          <Stack.Screen name="Record" component={RecordScreen} options={{ title: 'Record / Upload Voice' }} />
          <Stack.Screen name="Synthesis" component={SynthesisScreen} options={{ title: 'Generate Voice' }} />
        </Stack.Navigator>
      </NavigationContainer>
    </PaperProvider>
  );
}