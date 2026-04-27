import Canvas from '../ui/Canvas.js';
import LocalStorage from './LocalStorage.js';

/**
	 * Measure text width on canvas, using cached values when available.
	 */

/**
	 * @param {string} text Text to measure.
	 * @param {string} font Canvas font string.
	 * @returns {number} Measured text width in CSS pixels.
	 */
var measureText = function (text, font) {
	var width = LocalStorage.getCache(`Width.${font}${text}`);
	if (width === null) {
		var canvas = new Canvas({ id: 'metric', width: 50, height: 50, scale: 1 });
		canvas.context.font = font;
		width = canvas.context.measureText(text).width;
		LocalStorage.setCache(`Width.${font}${text}`, width);
	}
	return width;
};

/**
	 * Compute x/y padding required to center a single character of the font in
	 * a sizexsize box when drawing with textAlign=center and baseLine=middle.
	 *
	 * Values are cached using LocalStorage.
	 *
	 * @param {number} size Base text box size.
	 * @param {string} font Canvas font string.
	 * @param {string} [text='0'] Sample glyph used to estimate offsets.
	 * @returns {{x: number, y: number}} Offset values to add to draw coordinates.
	 */
var measureXAndYTextPadding = function (size, font, text) {
	if (typeof text == 'undefined') {
		text = '0';
	}
	var padding = LocalStorage.getCache(`Offset.${font}${text}`);
	if (padding === null) {
		var canvas = new Canvas({
			id: `metric${Math.floor((Math.random() * 100) + 1)}`,
			width: size * 3,
			height: size * 3,
			scale: (typeof window.devicePixelRatio === 'number') ? Math.round(window.devicePixelRatio * 8) : 8
		});
		if (canvas !== false) {
			try {
				var context = canvas.context;
				context.font = font;
				context.textAlign = 'center';
				context.textBaseline = 'middle';
				context.fillStyle = '#F00';
				context.fillText(text, size * 1.5, size * 1.5);

				var dim = size * 3 * canvas.scale,
					imageData = context.getImageData(0, 0, dim, dim),
					bottomOfText = false,
					topOfText = false,
					leftOfText = false,
					rightOfText = false,
					row, column;

				// Find top
				for (row = 0; topOfText === false && row < dim; ++row) {
					for (column = 0; column < dim ; ++column) {
						if (imageData.data[((row * (dim * 4)) + (column * 4))] > 0) {
							topOfText = row;
							break;
						}
					}
				}
				// Find left
				for (column = 0; leftOfText === false && column < dim; ++column) {
					for (row = topOfText; row < dim ; ++row) {
						if (imageData.data[((row * (dim * 4)) + (column * 4))] > 0) {
							leftOfText = column;
							break;
						}
					}
				}
				// Find bottom
				for (row = dim; bottomOfText === false && row > 0; --row) {
					for (column = leftOfText; column < dim ; ++column) {
						if (imageData.data[((row * (dim * 4)) + (column * 4))] > 0) {
							bottomOfText = row + 1;
							break;
						}
					}
				}
				// Find right
				for (column = dim; rightOfText === false && column > 0; --column) {
					for (row = topOfText; row < bottomOfText ; ++row) {
						if (imageData.data[((row * (dim * 4)) + (column * 4))] > 0) {
							rightOfText = column + 1;
							break;
						}
					}
				}

				padding = {
					x: Math.round(1000 * ((dim - rightOfText) - leftOfText) / (canvas.scale * 2)) / 1000,
					y: Math.round(1000 * ((dim - bottomOfText) - topOfText) / (canvas.scale * 2)) / 1000
				};

				LocalStorage.setCache(`Offset.${font}${text}`, padding);
			} catch (e) {
				padding = { x: 0, y: 0 };
			}
		}
		canvas = null;
	}
	return padding;
};

export { measureXAndYTextPadding };
export default measureText;
