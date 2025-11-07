// src/contexts/AuthContext.tsx
import React, { createContext, useState, useContext, useEffect } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API, resetAuthFailureState } from '../api';

interface User {
  id: number;
  first_name: string;
  middle_name?: string;
  last_name: string;
  email: string;
  phone_number?: string;
  gender?: string;
  birthday?: string;
  role?: string;
  email_verified_at?: string;
}

interface AuthContextData {
  user: User | null;
  token: string | null;
  loading: boolean;
  signIn: (token: string, userData: User) => Promise<void>;
  signOut: () => Promise<void>;
  updateUser: (userData: Partial<User>) => void;
}

const AuthContext = createContext<AuthContextData>({} as AuthContextData);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [token, setToken] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Load stored authentication data when the app starts
    loadStoredAuth();
  }, []);

  useEffect(() => {
    // Update API authorization header whenever token changes
    if (token) {
      API.defaults.headers = API.defaults.headers || {};
      API.defaults.headers.common = API.defaults.headers.common || {};
      API.defaults.headers.common['Authorization'] = `Bearer ${token}`;
      console.log('🔐 Updated API headers with stored token');
    } else {
      // Remove authorization header when token is null (logged out)
      if (API.defaults.headers?.common?.['Authorization']) {
        delete API.defaults.headers.common['Authorization'];
        console.log('🔓 Cleared API authorization header');
      }
    }
  }, [token]);

  const loadStoredAuth = async () => {
    try {
      setLoading(true);
      
      // Get stored token
      const storedToken = await AsyncStorage.getItem('token') || 
                          await AsyncStorage.getItem('userToken') || 
                          await AsyncStorage.getItem('accessToken');
      
      // Get stored user data
      const storedUserData = await AsyncStorage.getItem('user');
      const userData = storedUserData ? JSON.parse(storedUserData) : null;
      
      if (storedToken && userData) {
        // Set the auth state with stored data
        setToken(storedToken);
        setUser(userData);
        console.log('📂 Restored authentication from storage');
      } else {
        console.log('⚠️ No stored authentication found');
      }
    } catch (error) {
      console.error('Error loading authentication data:', error);
    } finally {
      setLoading(false);
    }
  };

  const signIn = async (newToken: string, userData: User) => {
    try {
      // Reset any pending auth failures
      resetAuthFailureState();
      
      // Save auth data to storage
      await AsyncStorage.setItem('token', newToken);
      await AsyncStorage.setItem('userToken', newToken); // For backward compatibility
      await AsyncStorage.setItem('user', JSON.stringify(userData));
      
      // Update state
      setToken(newToken);
      setUser(userData);
      console.log('🔑 User signed in successfully');
    } catch (error) {
      console.error('Error storing authentication data:', error);
      throw error;
    }
  };

  const signOut = async () => {
    try {
      // Reset any pending auth failures
      resetAuthFailureState();
      
      // Remove auth data from storage
      await AsyncStorage.removeItem('token');
      await AsyncStorage.removeItem('userToken');
      await AsyncStorage.removeItem('accessToken');
      await AsyncStorage.removeItem('user');
      await AsyncStorage.removeItem('verification_flow');
      
      // Clear state
      setToken(null);
      setUser(null);
      console.log('🚪 User signed out successfully');
    } catch (error) {
      console.error('Error during sign out:', error);
      throw error;
    }
  };

  const updateUser = (userData: Partial<User>) => {
    if (user) {
      const updatedUser = { ...user, ...userData };
      setUser(updatedUser);
      
      // Update stored user data
      AsyncStorage.setItem('user', JSON.stringify(updatedUser))
        .catch(error => console.error('Error updating stored user data:', error));
    }
  };

  return (
    <AuthContext.Provider value={{ user, token, loading, signIn, signOut, updateUser }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  
  return context;
};