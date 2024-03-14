/* global jest:false */
'use strict';

// Mock MW object
const mw = {
	log: {
		error: jest.fn(),
		warn: jest.fn()
	},
	config: {
		get: jest.fn()
	},
	user: {
		getId: jest.fn(),
		getName: jest.fn(),
		isAnon: jest.fn().mockReturnValue( true ),
		options: {
			get: jest.fn()
		}
	}
};

global.mw = mw;
