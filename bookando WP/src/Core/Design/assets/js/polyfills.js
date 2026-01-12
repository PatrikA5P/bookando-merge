// src/Core/Design/assets/js/polyfills.js
(function () {
  // String.prototype.startsWith
  if (!String.prototype.startsWith) {
    Object.defineProperty(String.prototype, 'startsWith', {
      value: function (search, pos) {
        pos = pos || 0;
        search = String(search);
        return this.substring(pos, pos + search.length) === search;
      },
      configurable: true,
      writable: true
    });
  }

  // String.prototype.includes
  if (!String.prototype.includes) {
    Object.defineProperty(String.prototype, 'includes', {
      value: function (search, start) {
        if (search instanceof RegExp) throw new TypeError('first argument must not be a RegExp');
        if (start === undefined) start = 0;
        return this.indexOf(String(search), start) !== -1;
      },
      configurable: true,
      writable: true
    });
  }

  // Array.prototype.includes
  if (!Array.prototype.includes) {
    Object.defineProperty(Array.prototype, 'includes', {
      value: function (search, fromIndex) {
        var O = Object(this);
        var len = parseInt(O.length, 10) || 0;
        if (len === 0) return false;
        var k = Math.max(fromIndex | 0, 0);
        while (k < len) {
          if (O[k] === search || (Number.isNaN(O[k]) && Number.isNaN(search))) return true;
          k++;
        }
        return false;
      },
      configurable: true,
      writable: true
    });
  }

  // ⬇️ String.prototype.padStart (wird von deinem formatters.ts benutzt)
  if (!String.prototype.padStart) {
    Object.defineProperty(String.prototype, 'padStart', {
      value: function padStart(targetLength, padString) {
        targetLength = targetLength >> 0; // to uint
        padString = String(padString || ' ');
        if (this.length >= targetLength) return String(this);
        targetLength = targetLength - this.length;
        if (targetLength > padString.length) {
          padString += padString.repeat(Math.ceil(targetLength / padString.length));
        }
        return padString.slice(0, targetLength) + String(this);
      },
      configurable: true,
      writable: true
    });
  }
})();
