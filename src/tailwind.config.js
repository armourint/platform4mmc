/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/views/**/*.blade.php",
    "./app/Livewire/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        ink: {
          900:"#0f172a", 800:"#1e293b", 700:"#334155",
          600:"#475569", 500:"#64748b",
        },
        brand: { DEFAULT:"#5b2d82", 600:"#4c2570", 700:"#3f1f60" },
        risk:  { green:"#22c55e", amber:"#f59e0b", red:"#ef4444" },
      },
      borderRadius: { xl: "1rem", "2xl":"1.25rem" },
      boxShadow: { soft: "0 8px 24px -12px rgb(0 0 0 / 0.15)" },
      fontFamily: { sans: ["Inter","ui-sans-serif","system-ui","Segoe UI","Roboto","Arial","sans-serif"] },
    },
  },
  plugins: [
    require("@tailwindcss/forms"),
    require("@tailwindcss/typography"),
  ],
};
