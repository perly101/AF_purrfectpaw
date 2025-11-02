import React from 'react';
import { View, Text, StyleSheet, ScrollView } from 'react-native';
import { getResponsiveDebugInfo, getResponsiveTabBarSizes } from '../src/utils/responsiveTabBar';

/**
 * Debug component to display responsive tab bar sizing information
 * This component can be temporarily added to any screen to verify responsive behavior
 */
export default function ResponsiveTabBarDebug() {
  const debugInfo = getResponsiveDebugInfo();
  const sizes = getResponsiveTabBarSizes();

  const renderDebugItem = (label: string, value: number | string) => (
    <View style={styles.debugItem} key={label}>
      <Text style={styles.debugLabel}>{label}:</Text>
      <Text style={styles.debugValue}>{typeof value === 'number' ? value.toFixed(1) : value}</Text>
    </View>
  );

  return (
    <ScrollView style={styles.container}>
      <Text style={styles.title}>Responsive Tab Bar Debug Info</Text>
      
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Screen Information</Text>
        {renderDebugItem('Screen Width', debugInfo.screenWidth)}
        {renderDebugItem('Screen Height', debugInfo.screenHeight)}
        {renderDebugItem('Base Width', debugInfo.baseWidth)}
        {renderDebugItem('Scale Factor', debugInfo.scale)}
        {renderDebugItem('Pixel Ratio', debugInfo.pixelRatio)}
        {renderDebugItem('Font Scale', debugInfo.fontScale)}
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Tab Bar Dimensions</Text>
        {renderDebugItem('Tab Bar Height', sizes.tabBarHeight)}
        {renderDebugItem('Pill Border Radius', sizes.pillCardBorderRadius)}
        {renderDebugItem('Container Padding H', sizes.containerPaddingHorizontal)}
        {renderDebugItem('Container Padding Top', sizes.containerPaddingTop)}
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Active Circle</Text>
        {renderDebugItem('Circle Size', sizes.activeCircleSize)}
        {renderDebugItem('Circle Border Radius', sizes.activeCircleBorderRadius)}
        {renderDebugItem('Circle Overlap', sizes.activeCircleOverlap)}
        <Text style={styles.constraintText}>
          Constraint: 48-80px (Current: {sizes.activeCircleSize}px)
        </Text>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Icons & Labels</Text>
        {renderDebugItem('Active Icon Size', sizes.iconSize)}
        {renderDebugItem('Inactive Icon Size', sizes.inactiveIconSize)}
        {renderDebugItem('Label Font Size', sizes.labelFontSize)}
        <Text style={styles.constraintText}>
          Icon Constraint: 18-36px (Current: {sizes.iconSize}px)
        </Text>
        <Text style={styles.constraintText}>
          Label Constraint: 10-16px (Current: {sizes.labelFontSize}px)
        </Text>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Accessibility</Text>
        {renderDebugItem('Min Touch Target', sizes.minTouchTarget)}
        <Text style={styles.constraintText}>
          Minimum: 48px (Current: {sizes.minTouchTarget}px)
        </Text>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Spacing</Text>
        {renderDebugItem('Tab Item Padding Bottom', sizes.tabItemPaddingBottom)}
        {renderDebugItem('Label Margin Top', sizes.labelMarginTop)}
        {renderDebugItem('Inactive Icon Margin Bottom', sizes.inactiveIconMarginBottom)}
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Shadow & Elevation</Text>
        {renderDebugItem('Shadow Radius', sizes.shadowRadius)}
        {renderDebugItem('Shadow Offset Width', sizes.shadowOffset.width)}
        {renderDebugItem('Shadow Offset Height', sizes.shadowOffset.height)}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
    padding: 16,
  },
  title: {
    fontSize: 20,
    fontWeight: 'bold',
    textAlign: 'center',
    marginBottom: 20,
    color: '#333',
  },
  section: {
    backgroundColor: 'white',
    borderRadius: 8,
    padding: 16,
    marginBottom: 16,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    marginBottom: 12,
    color: '#0AA3FF',
  },
  debugItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 4,
  },
  debugLabel: {
    fontSize: 14,
    color: '#666',
    flex: 1,
  },
  debugValue: {
    fontSize: 14,
    fontWeight: '500',
    color: '#333',
  },
  constraintText: {
    fontSize: 12,
    color: '#888',
    fontStyle: 'italic',
    marginTop: 4,
  },
});