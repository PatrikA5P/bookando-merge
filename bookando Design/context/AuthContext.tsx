/**
 * Authentication Context
 *
 * Verwaltet den globalen Authentication-State und
 * stellt Auth-Funktionen für die gesamte App bereit.
 */

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import authService, { LoginCredentials, LoginResponse } from '../services/auth.service';

interface AuthContextType {
  user: LoginResponse['user'] | null;
  organization: LoginResponse['organization'] | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (credentials: LoginCredentials) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

interface AuthProviderProps {
  children: ReactNode;
}

export function AuthProvider({ children }: AuthProviderProps) {
  const [user, setUser] = useState<LoginResponse['user'] | null>(null);
  const [organization, setOrganization] = useState<LoginResponse['organization'] | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  // Check if user is already logged in on mount
  useEffect(() => {
    const initAuth = () => {
      const storedUser = authService.getCurrentUser();
      const orgId = authService.getOrganizationId();

      if (storedUser && orgId) {
        setUser(storedUser);
        // Organization wird beim Login gesetzt, hier nur ID verfügbar
        // Bei Bedarf könnte man hier einen API-Call machen
      }

      setIsLoading(false);
    };

    initAuth();
  }, []);

  const login = async (credentials: LoginCredentials) => {
    setIsLoading(true);
    try {
      const response = await authService.login(credentials);
      setUser(response.user);
      setOrganization(response.organization);
    } finally {
      setIsLoading(false);
    }
  };

  const logout = async () => {
    setIsLoading(true);
    try {
      await authService.logout();
      setUser(null);
      setOrganization(null);
    } finally {
      setIsLoading(false);
    }
  };

  const refreshUser = () => {
    const storedUser = authService.getCurrentUser();
    setUser(storedUser);
  };

  const value: AuthContextType = {
    user,
    organization,
    isAuthenticated: !!user,
    isLoading,
    login,
    logout,
    refreshUser,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

export default AuthContext;
