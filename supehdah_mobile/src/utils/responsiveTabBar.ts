import { Dimensions, PixelRatio } from 'react-native';

const { width: screenWidth, height: screenHeight } = Dimensions.get('window');

// Base design targets iPhone 11 (375px width)
const BASE_WIDTH = 375;

// Calculate responsive scale factor
const scale = screenWidth / BASE_WIDTH;

export interface ResponsiveTabBarSizes {
  // Container and bar dimensions
  tabBarHeight: number;
  pillCardBorderRadius: number;
  containerPaddingHorizontal: number;
  containerPaddingTop: number;
  
  // Active circle dimensions
  activeCircleSize: number;
  activeCircleBorderRadius: number;
  activeCircleOverlap: number; // Overlap amount (~15% of circle diameter)
  
  // Icon dimensions
  iconSize: number;
  inactiveIconSize: number;
  
  // Label dimensions
  labelFontSize: number;
  
  // Spacing
  tabItemPaddingBottom: number;
  labelMarginTop: number;
  inactiveIconMarginBottom: number;
  
  // Touch targets (ensures accessibility)
  minTouchTarget: number;
  
  // Shadow and elevation
  shadowRadius: number;
  shadowOffset: { width: number; height: number };
}

/**
 * Calculates responsive sizes for tab bar components with proper constraints
 * @returns Object containing all responsive dimensions for tab bars
 */
export function getResponsiveTabBarSizes(): ResponsiveTabBarSizes {
  // Helper function to constrain values within min/max bounds
  const constrain = (value: number, min: number, max: number): number => {
    return Math.max(min, Math.min(max, value));
  };

  // Calculate base sizes with scaling
  const baseActiveCircle = 56 * scale;
  const baseIcon = 24 * scale;
  const baseLabel = 12 * scale;
  const baseTouchTarget = 48 * scale;

  // Apply constraints as specified in requirements
  const activeCircleSize = constrain(Math.round(baseActiveCircle), 48, 80);
  const iconSize = constrain(Math.round(baseIcon), 18, 36);
  const labelFontSize = constrain(Math.round(baseLabel), 10, 16);
  
  // Ensure minimum touch target of 48px
  const minTouchTarget = Math.max(48, Math.round(baseTouchTarget));
  
  // Calculate overlap as ~10% of circle diameter (reduced for better fit)
  const activeCircleOverlap = Math.round(activeCircleSize * 0.1);
  
  // Calculate other responsive dimensions - keep consistent height
  const tabBarHeight = 85; // Fixed height to prevent floating issues
  const pillCardBorderRadius = Math.round(28 * scale);
  const containerPaddingHorizontal = Math.round(16 * scale);
  const containerPaddingTop = Math.round(8 * scale);
  
  // Inactive icon is slightly smaller than active icon for visual hierarchy
  const inactiveIconSize = Math.round(iconSize * 0.85);
  
  // Spacing calculations
  const tabItemPaddingBottom = Math.round(4 * scale);
  const labelMarginTop = Math.round(4 * scale);
  const inactiveIconMarginBottom = Math.round(4 * scale);
  
  // Shadow and elevation scaling
  const shadowRadius = Math.round(8 * scale);
  const shadowOffset = {
    width: 0,
    height: Math.round(4 * scale)
  };

  return {
    tabBarHeight,
    pillCardBorderRadius,
    containerPaddingHorizontal,
    containerPaddingTop,
    
    activeCircleSize,
    activeCircleBorderRadius: Math.round(activeCircleSize / 2),
    activeCircleOverlap,
    
    iconSize,
    inactiveIconSize,
    
    labelFontSize,
    
    tabItemPaddingBottom,
    labelMarginTop,
    inactiveIconMarginBottom,
    
    minTouchTarget,
    
    shadowRadius,
    shadowOffset
  };
}

/**
 * Gets responsive font size with pixel ratio consideration
 * @param size Base font size
 * @returns Responsive font size
 */
export function getResponsiveFontSize(size: number): number {
  const scaledSize = size * scale;
  return Math.round(PixelRatio.roundToNearestPixel(scaledSize));
}

/**
 * Gets responsive size with constraints
 * @param size Base size to scale
 * @param min Minimum allowed value
 * @param max Maximum allowed value
 * @returns Constrained responsive size
 */
export function getResponsiveSize(size: number, min?: number, max?: number): number {
  const scaledSize = Math.round(size * scale);
  
  if (min !== undefined && max !== undefined) {
    return Math.max(min, Math.min(max, scaledSize));
  }
  
  return scaledSize;
}

/**
 * Calculates elevation for Android based on scale
 * @param baseElevation Base elevation value
 * @returns Scaled elevation
 */
export function getResponsiveElevation(baseElevation: number): number {
  return Math.round(baseElevation * scale);
}

/**
 * Debug information about current screen and scaling
 * @returns Object with debug info
 */
export function getResponsiveDebugInfo() {
  const sizes = getResponsiveTabBarSizes();
  
  return {
    screenWidth,
    screenHeight,
    baseWidth: BASE_WIDTH,
    scale,
    pixelRatio: PixelRatio.get(),
    fontScale: PixelRatio.getFontScale(),
    activeCircleSize: sizes.activeCircleSize,
    iconSize: sizes.iconSize,
    labelFontSize: sizes.labelFontSize,
    minTouchTarget: sizes.minTouchTarget
  };
}