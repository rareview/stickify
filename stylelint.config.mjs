/** @type {import('stylelint').Config} */
export default {
	"extends": "@wordpress/stylelint-config",
	"ignoreFiles": [
		"vendor/**/*",
		"node_modules/**/*",
		"dist/**/*"
	],
	"rules": {
        "custom-property-pattern": null,
        "at-rule-no-unknown": [
            true,
            {
                "ignoreAtRules": [
					"mixin",
					"if",
					"include",
					"else",
					"for",
					"each",
					"function",
					"return",
					"use"
                ]
            }
        ],
        "selector-type-no-unknown": null,
        "at-rule-empty-line-before": null,
        "selector-nested-pattern": null,
		"no-descending-specificity": null,
		"declaration-empty-line-before": null,
		"shorthand-property-no-redundant-values": null,
		"declaration-block-no-redundant-longhand-properties": null,
		"selector-class-pattern": null,
		"color-hex-length": null,
    }
};
