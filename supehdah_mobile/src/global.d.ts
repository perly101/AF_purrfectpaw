// Project-wide global type declarations
// Prevent TypeScript editor errors for React Native globals like __DEV__
// Use module augmentation so this file can be included safely without
// causing duplicate identifier errors when other type packages also
// declare __DEV__.
export {};
declare global {
	const __DEV__: boolean;
}
