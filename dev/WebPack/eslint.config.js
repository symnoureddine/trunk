module.exports = {
  "root":          true,
  "env":           {
    "node": true
  },
  "extends":       [
    "plugin:vue/essential",
    "@vue/standard",
    "@vue/typescript/recommended"
  ],
  "overrides":     [
    {
      "files": [
        "**/__tests__/*.{j,t}s?(x)",
        "**/tests/unit/**/*.spec.{j,t}s?(x)"
      ],
      "env":   {
        "jest": true
      }
    }
  ],
  "parserOptions": {
    "ecmaVersion": 2020
  },
  "rules":         {
    "quotes":                                    ["error", "double"],
    "indent":                                    ["error", 4],
    "@typescript-eslint/member-delimiter-style": [
      "error",
      {
        "multiline":  {
          "delimiter": "none"
        },
        "singleline": {
          "delimiter": "semi"
        }
      }
    ],
    "@typescript-eslint/camelcase":              "off",
    "brace-style":                               ["error", "stroustrup"]
  }
}
