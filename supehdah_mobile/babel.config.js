module.exports = function (api) {
	api.cache(true);

	return {
		// Use the Expo preset (works with React Native + TypeScript projects)
		// and add Flow preset so Flow-typed files inside node_modules (e.g. expo)
		// can be parsed during bundling.
		presets: ["babel-preset-expo", "@babel/preset-flow"],
		plugins: [],
	};
};
