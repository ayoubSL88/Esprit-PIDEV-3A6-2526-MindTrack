/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.twig",
    "./src/**/*.php"
  ],
  theme: {
    extend: {
      colors: {
        surface: "#ffffff",
        background: "#f7f8ff",
        panel: "#f7f8ff",
        border: "#c9ccff",
        ink: "#28324a",
        muted: "#5f6b8f",
        brand: {
          DEFAULT: "#a2a4f2",
          dark: "#8588dc",
          soft: "#c9ccff"
        },
        accent: {
          DEFAULT: "#f4b740",
          soft: "#fde8b5"
        },
        emphasis: {
          DEFAULT: "#f28c38",
          soft: "#f9d0ad"
        },
        success: {
          DEFAULT: "#2f8f6b",
          soft: "#d8f2e8",
          border: "#92d5bc"
        },
        danger: {
          DEFAULT: "#c46045",
          soft: "#f8ddd0",
          border: "#eab19d"
        }
      },
      boxShadow: {
        shell: "0 20px 45px -28px rgba(18, 33, 46, 0.35)",
        soft: "0 18px 40px -28px rgba(31, 41, 55, 0.22)"
      },
      borderRadius: {
        shell: "1.5rem"
      },
      fontFamily: {
        sans: ["Segoe UI", "Tahoma", "Geneva", "Verdana", "sans-serif"]
      }
    }
  },
  plugins: []
};
