import React, { useState, useRef } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  StyleSheet,
  Animated,
  Dimensions,
  Platform,
} from 'react-native';
import { Ionicons } from '@expo/vector-icons';

const { width, height } = Dimensions.get('window');

// Pink theme colors
const MAIN_COLOR = '#E91E63';
const MAIN_COLOR_DARK = '#C2185B';
const WHITE = '#FFFFFF';
const BLACK = '#000000';
const SHADOW_COLOR = '#000000';
const BACKDROP_COLOR = 'rgba(0, 0, 0, 0.3)';
const LABEL_BG = 'rgba(0, 0, 0, 0.8)';

type MenuAction = {
  id: string;
  icon: keyof typeof Ionicons.glyphMap;
  label: string;
  onPress: () => void;
};

type FloatingActionMenuProps = {
  actions: MenuAction[];
  style?: any;
};

const FloatingActionMenu: React.FC<FloatingActionMenuProps> = ({ actions, style }) => {
  const [isOpen, setIsOpen] = useState(false);
  
  // Animation values
  const fabRotation = useRef(new Animated.Value(0)).current;
  const fabPulse = useRef(new Animated.Value(1)).current;
  const backdropOpacity = useRef(new Animated.Value(0)).current;
  const actionAnimations = useRef(
    actions.map(() => ({
      translateY: new Animated.Value(0),
      opacity: new Animated.Value(0),
      scale: new Animated.Value(0.3),
    }))
  ).current;

  // Pulse animation for main FAB when closed
  React.useEffect(() => {
    if (!isOpen) {
      const pulseAnimation = Animated.loop(
        Animated.sequence([
          Animated.timing(fabPulse, {
            toValue: 1.1,
            duration: 1000,
            useNativeDriver: true,
          }),
          Animated.timing(fabPulse, {
            toValue: 1,
            duration: 1000,
            useNativeDriver: true,
          }),
        ])
      );
      pulseAnimation.start();
      return () => pulseAnimation.stop();
    } else {
      Animated.timing(fabPulse, {
        toValue: 1,
        duration: 200,
        useNativeDriver: true,
      }).start();
    }
  }, [isOpen, fabPulse]);

  const toggleMenu = () => {
    const toValue = isOpen ? 0 : 1;

    // Animate main FAB rotation
    Animated.spring(fabRotation, {
      toValue,
      useNativeDriver: true,
      tension: 100,
      friction: 8,
    }).start();

    // Animate backdrop
    Animated.timing(backdropOpacity, {
      toValue,
      duration: 200,
      useNativeDriver: true,
    }).start();

    // Animate action buttons with proper spacing
    if (!isOpen) {
      // Opening: stagger each button
      actionAnimations.forEach((anim, index) => {
        const delay = index * 50;
        const distance = 0 + (index * 115); // Fixed spacing: 70, 135, 200px
        
        Animated.parallel([
          Animated.spring(anim.translateY, {
            toValue: -distance,
            useNativeDriver: true,
            tension: 120,
            friction: 8,
            delay,
          }),
          Animated.spring(anim.opacity, {
            toValue: 1,
            useNativeDriver: true,
            delay,
          }),
          Animated.spring(anim.scale, {
            toValue: 1,
            useNativeDriver: true,
            tension: 120,
            friction: 8,
            delay,
          }),
        ]).start();
      });
    } else {
      // Closing: reverse order
      actionAnimations.slice().reverse().forEach((anim, reverseIndex) => {
        const delay = reverseIndex * 25;
        
        Animated.parallel([
          Animated.spring(anim.translateY, {
            toValue: 0,
            useNativeDriver: true,
            tension: 150,
            friction: 10,
            delay,
          }),
          Animated.timing(anim.opacity, {
            toValue: 0,
            duration: 100,
            useNativeDriver: true,
            delay,
          }),
          Animated.spring(anim.scale, {
            toValue: 0,
            useNativeDriver: true,
            tension: 150,
            friction: 10,
            delay,
          }),
        ]).start();
      });
    }

    setIsOpen(!isOpen);
  };

  const triggerHapticFeedback = () => {
    try {
      const Haptics = require('expo-haptics');
      Haptics.impactAsync(Haptics.ImpactFeedbackStyle.Light);
    } catch {
      // Haptic feedback not available
    }
  };

  const handleActionPress = (action: MenuAction) => {
    console.log(`🎯 FAM Button pressed: ${action.label} (${action.id})`);
    triggerHapticFeedback();
    
    try {
      action.onPress();
      console.log(`✅ Successfully called onPress for ${action.label}`);
    } catch (error) {
      console.error(`❌ Error calling onPress for ${action.label}:`, error);
    }
    
    toggleMenu(); // Close menu after action
  };

  const handleMainFabPress = () => {
    triggerHapticFeedback();
    toggleMenu();
  };

  const fabRotationInterpolate = fabRotation.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '45deg'],
  });

  return (
    <View style={[styles.container, style]} pointerEvents="box-none">
      {/* Backdrop */}
      {isOpen && (
        <Animated.View
          style={[
            styles.backdrop,
            {
              opacity: backdropOpacity,
            },
          ]}
          pointerEvents={isOpen ? 'auto' : 'none'}
        >
          <TouchableOpacity
            style={StyleSheet.absoluteFillObject}
            onPress={toggleMenu}
            activeOpacity={1}
          />
        </Animated.View>
      )}

      {/* Action Buttons */}
      <View style={styles.actionContainer} pointerEvents="box-none">
        {actions.map((action, index) => (
          <Animated.View
            key={action.id}
            style={[
              styles.actionWrapper,
              {
                transform: [
                  { translateY: actionAnimations[index].translateY },
                  { scale: actionAnimations[index].scale },
                ],
                opacity: actionAnimations[index].opacity,
              },
            ]}
            pointerEvents={isOpen ? 'auto' : 'none'}
          >
            {/* Action Label */}
            <Animated.View
              style={[
                styles.labelContainer,
                {
                  opacity: actionAnimations[index].opacity,
                },
              ]}
            >
              <Text style={styles.labelText}>{action.label}</Text>
            </Animated.View>

            {/* Action Button */}
            <TouchableOpacity
              style={styles.actionButton}
              onPress={() => handleActionPress(action)}
              activeOpacity={0.8}
            >
              <Ionicons name={action.icon} size={20} color={WHITE} />
            </TouchableOpacity>
          </Animated.View>
        ))}
      </View>

      {/* Main FAB */}
      <Animated.View
        style={[
          styles.mainFab,
          {
            transform: [{ scale: fabPulse }],
          },
        ]}
      >
        <TouchableOpacity
          style={styles.mainFabTouchable}
          onPress={handleMainFabPress}
          activeOpacity={0.8}
        >
          <Animated.View
            style={[
              styles.mainFabInner,
              {
                transform: [{ rotate: fabRotationInterpolate }],
              },
            ]}
          >
            <Ionicons name="add" size={26} color={WHITE} />
          </Animated.View>
        </TouchableOpacity>
      </Animated.View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    position: 'absolute',
    bottom: 30,
    right: 20,
    zIndex: 1000,
    alignItems: 'flex-end',
    justifyContent: 'flex-end',
  },
  backdrop: {
    position: 'absolute',
    top: -height * 2,
    left: -width * 2,
    width: width * 4,
    height: height * 4,
    backgroundColor: BACKDROP_COLOR,
    zIndex: 998,
  },
  actionContainer: {
    alignItems: 'flex-end',
    justifyContent: 'flex-end',
    position: 'absolute',
    bottom: 1, // Distance from main FAB
    right: 0,
    zIndex: 999,
    width: 200, // Fixed width to prevent overflow
  },
  actionWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'flex-end',
    marginBottom: 5, // Minimal margin since we control spacing via translateY
    width: '100%',
  },
  labelContainer: {
    backgroundColor: LABEL_BG,
    borderRadius: 20,
    paddingHorizontal: 12,
    paddingVertical: 6,
    marginRight: 12,
    maxWidth: 100,
    minWidth: 60,
    shadowColor: SHADOW_COLOR,
    shadowOffset: {
      width: 0,
      height: 2,
    },
    shadowOpacity: 0.2,
    shadowRadius: 3,
    elevation: 3,
  },
  labelText: {
    color: WHITE,
    fontSize: 11,
    fontWeight: '600',
    textAlign: 'center',
    letterSpacing: 0.3,
  },
  actionButton: {
    width: 50,
    height: 50,
    borderRadius: 25,
    backgroundColor: MAIN_COLOR,
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: MAIN_COLOR,
    shadowOffset: {
      width: 0,
      height: 3,
    },
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 6,
    borderWidth: 2,
    borderColor: WHITE,
  },
  mainFab: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: MAIN_COLOR,
    shadowColor: MAIN_COLOR,
    shadowOffset: {
      width: 0,
      height: 4,
    },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 8,
    borderWidth: 3,
    borderColor: WHITE,
    zIndex: 1000,
  },
  mainFabTouchable: {
    width: '100%',
    height: '100%',
    borderRadius: 30,
    alignItems: 'center',
    justifyContent: 'center',
  },
  mainFabInner: {
    alignItems: 'center',
    justifyContent: 'center',
  },
});

export default FloatingActionMenu;