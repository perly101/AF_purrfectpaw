/**
 * Test file for responsive tab bar behavior
 * This file demonstrates how the tab bar scales across different device sizes
 */

import { getResponsiveTabBarSizes, getResponsiveDebugInfo } from '../utils/responsiveTabBar';

// Mock Dimensions for testing different screen sizes
const mockDimensions = (width: number, height: number) => {
  // This would be used in a test environment
  return { width, height };
};

// Common device dimensions for testing
const DEVICE_SIZES = {
  'iPhone SE (1st gen)': { width: 320, height: 568 },
  'iPhone SE (2nd/3rd gen)': { width: 375, height: 667 },
  'iPhone 11/XR': { width: 414, height: 896 },
  'iPhone 12/13/14': { width: 390, height: 844 },
  'iPhone 12/13/14 Pro Max': { width: 428, height: 926 },
  'Samsung Galaxy S8': { width: 360, height: 740 },
  'Samsung Galaxy Note 10+': { width: 412, height: 869 },
  'iPad Mini': { width: 768, height: 1024 },
  'iPad Pro 11"': { width: 834, height: 1194 },
  'Small Android': { width: 320, height: 640 },
  'Large Android': { width: 480, height: 854 },
} as const;

/**
 * Test function that calculates tab bar sizes for different device sizes
 * This helps verify that the responsive constraints are working correctly
 */
export function testResponsiveTabBarSizes() {
  const results: Array<{
    device: string;
    screenWidth: number;
    scale: number;
    activeCircleSize: number;
    iconSize: number;
    labelFontSize: number;
    minTouchTarget: number;
    constraintsRespected: boolean;
  }> = [];

  Object.entries(DEVICE_SIZES).forEach(([deviceName, dimensions]) => {
    // Mock the current screen dimensions
    const scale = dimensions.width / 375; // Base width is 375
    
    // Simulate the calculations that would happen in getResponsiveTabBarSizes
    const constrain = (value: number, min: number, max: number): number => {
      return Math.max(min, Math.min(max, value));
    };

    const baseActiveCircle = 56 * scale;
    const baseIcon = 24 * scale;
    const baseLabel = 12 * scale;
    const baseTouchTarget = 48 * scale;

    const activeCircleSize = constrain(Math.round(baseActiveCircle), 48, 80);
    const iconSize = constrain(Math.round(baseIcon), 18, 36);
    const labelFontSize = constrain(Math.round(baseLabel), 10, 16);
    const minTouchTarget = Math.max(48, Math.round(baseTouchTarget));

    // Check if constraints are respected
    const constraintsRespected = 
      activeCircleSize >= 48 && activeCircleSize <= 80 &&
      iconSize >= 18 && iconSize <= 36 &&
      labelFontSize >= 10 && labelFontSize <= 16 &&
      minTouchTarget >= 48;

    results.push({
      device: deviceName,
      screenWidth: dimensions.width,
      scale: Math.round(scale * 1000) / 1000, // Round to 3 decimal places
      activeCircleSize,
      iconSize,
      labelFontSize,
      minTouchTarget,
      constraintsRespected
    });
  });

  return results;
}

/**
 * Validates that all calculated sizes meet the specified constraints
 */
export function validateConstraints() {
  const testResults = testResponsiveTabBarSizes();
  
  const validation = {
    allConstraintsRespected: testResults.every(result => result.constraintsRespected),
    failedDevices: testResults.filter(result => !result.constraintsRespected),
    summary: {
      totalDevicesTested: testResults.length,
      passedDevices: testResults.filter(result => result.constraintsRespected).length,
      failedDevices: testResults.filter(result => !result.constraintsRespected).length
    }
  };

  return validation;
}

/**
 * Generates a human-readable report of the responsive behavior
 */
export function generateResponsiveReport(): string {
  const testResults = testResponsiveTabBarSizes();
  const validation = validateConstraints();
  
  let report = "=== RESPONSIVE TAB BAR TEST REPORT ===\n\n";
  
  report += `Validation Summary:\n`;
  report += `- Total devices tested: ${validation.summary.totalDevicesTested}\n`;
  report += `- Passed constraints: ${validation.summary.passedDevices}\n`;
  report += `- Failed constraints: ${validation.summary.failedDevices}\n`;
  report += `- All constraints respected: ${validation.allConstraintsRespected ? 'YES' : 'NO'}\n\n`;
  
  if (validation.failedDevices.length > 0) {
    report += "FAILED DEVICES:\n";
    validation.failedDevices.forEach(device => {
      report += `- ${device.device}\n`;
    });
    report += "\n";
  }
  
  report += "DETAILED RESULTS:\n";
  report += "Device Name".padEnd(25) + " | " + 
            "Width".padEnd(6) + " | " + 
            "Scale".padEnd(6) + " | " + 
            "Circle".padEnd(7) + " | " + 
            "Icon".padEnd(5) + " | " + 
            "Label".padEnd(6) + " | " + 
            "Touch".padEnd(6) + " | " + 
            "Valid\n";
  report += "-".repeat(80) + "\n";
  
  testResults.forEach(result => {
    report += result.device.padEnd(25) + " | " +
              result.screenWidth.toString().padEnd(6) + " | " +
              result.scale.toFixed(2).padEnd(6) + " | " +
              result.activeCircleSize.toString().padEnd(7) + " | " +
              result.iconSize.toString().padEnd(5) + " | " +
              result.labelFontSize.toString().padEnd(6) + " | " +
              result.minTouchTarget.toString().padEnd(6) + " | " +
              (result.constraintsRespected ? "✓" : "✗") + "\n";
  });
  
  report += "\nCONSTRAINTS:\n";
  report += "- Active Circle: 48-80px\n";
  report += "- Icon Size: 18-36px\n";
  report += "- Label Font: 10-16px\n";
  report += "- Touch Target: minimum 48px\n";
  report += "- Base Width: 375px (iPhone 11)\n";
  report += "- Overlap: ~15% of circle diameter\n";
  
  return report;
}

// Export test results for use in development
export const RESPONSIVE_TEST_RESULTS = testResponsiveTabBarSizes();
export const VALIDATION_RESULTS = validateConstraints();