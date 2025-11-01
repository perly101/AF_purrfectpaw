// This file provides polyfills for React Navigation under Hermes engine
// No direct imports to avoid resolution errors

// Polyfills for Hermes engine in React Native
// Fix for "Cannot read property 'S' of undefined" error
if (typeof global.__S === 'undefined') {
  global.__S = (id) => id;
}

// Fix for "Cannot read property 'default' of undefined"
if (!global.HermesInternal) {
  global.HermesInternal = {};
}

// Fix missing Map/Set implementations
if (typeof global.Map === 'undefined') {
  global.Map = function Map() {
    this._data = {};
    this.size = 0;
  };
  
  global.Map.prototype.set = function(key, value) {
    const stringKey = String(key);
    if (!this._data[stringKey]) this.size++;
    this._data[stringKey] = value;
    return this;
  };
  
  global.Map.prototype.get = function(key) {
    return this._data[String(key)];
  };
  
  global.Map.prototype.has = function(key) {
    return this._data.hasOwnProperty(String(key));
  };
  
  global.Map.prototype.delete = function(key) {
    const stringKey = String(key);
    if (this._data[stringKey]) {
      delete this._data[stringKey];
      this.size--;
      return true;
    }
    return false;
  };
  
  global.Map.prototype.clear = function() {
    this._data = {};
    this.size = 0;
  };
}

// Add missing methods to Array
if (!Array.prototype.find) {
  Array.prototype.find = function(predicate) {
    for (let i = 0; i < this.length; i++) {
      if (predicate(this[i], i, this)) {
        return this[i];
      }
    }
    return undefined;
  };
}

if (!Array.prototype.includes) {
  Array.prototype.includes = function(searchElement, fromIndex) {
    fromIndex = fromIndex || 0;
    for (let i = fromIndex; i < this.length; i++) {
      if (this[i] === searchElement) {
        return true;
      }
    }
    return false;
  };
}

// Add missing methods to String
if (!String.prototype.includes) {
  String.prototype.includes = function(search, start) {
    return this.indexOf(search, start) !== -1;
  };
}