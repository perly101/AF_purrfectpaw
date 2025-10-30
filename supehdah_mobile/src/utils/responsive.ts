import { Dimensions, PixelRatio } from 'react-native';

// Base guideline width for scaling (iPhone 6/7/8)
const guidelineBaseWidth = 375;

const { width: deviceWidth, height: deviceHeight } = Dimensions.get('window');

/**
 * Scale a size (width/height) based on device width
 */
export const normalize = (size: number) => {
	const scale = deviceWidth / guidelineBaseWidth;
	const newSize = size * scale;
	return Math.round(PixelRatio.roundToNearestPixel(newSize));
};

/**
 * Normalize font size (separate in case you want different scaling)
 */
export const normalizeFont = (size: number) => normalize(size);

export const spacing = {
	xs: 4,
	sm: 8,
	md: 16,
	lg: 24,
	xl: 32,
};

export const getBreakpoint = () => {
	if (deviceWidth < 360) return 'xs';
	if (deviceWidth < 768) return 'sm';
	if (deviceWidth < 1024) return 'md';
	return 'lg';
};

export { deviceWidth, deviceHeight };
