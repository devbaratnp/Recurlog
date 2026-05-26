declare module 'expo-router' {
  import { ComponentType, ReactNode } from 'react';
  import { ViewStyle, TextStyle } from 'react-native';

  export function useRouter(): {
    push: (href: string) => void;
    replace: (href: string) => void;
    back: () => void;
  };

  export function useLocalSearchParams<T extends Record<string, string | string[]>>(): T;

  export function Stack(props: {
    screenOptions?: { headerShown?: boolean };
    children?: ReactNode;
  }): JSX.Element;

  export namespace Stack {
    export function Screen(props: {
      name?: string;
      options?: Record<string, any>;
    }): JSX.Element;
  }

  export function Tabs(props: {
    screenOptions?: {
      headerShown?: boolean;
      tabBarActiveTintColor?: string;
      tabBarInactiveTintColor?: string;
      tabBarStyle?: ViewStyle;
      tabBarLabelStyle?: TextStyle;
    };
    children?: ReactNode;
  }): JSX.Element;

  export namespace Tabs {
    export function Screen(props: {
      name: string;
      options?: {
        title?: string;
        tabBarIcon?: (props: { color: string; size: number }) => JSX.Element;
      };
    }): JSX.Element;
  }
}
