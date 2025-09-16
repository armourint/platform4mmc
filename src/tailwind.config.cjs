module.exports = {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.js",
    "./storage/framework/views/*.php",
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php"
  ],
  theme: { extend: {} },
  safelist: [
    "min-h-screen","bg-gray-100","rounded-2xl","text-gray-700",
    "focus:ring-2","focus:ring-blue-500","focus:outline-none",
    "w-full","max-w-md","shadow-lg","p-8","text-2xl","font-bold",
    "text-center","text-gray-800","space-y-6","mt-1","block",
    "rounded-lg","border-gray-300","sm:text-sm","flex","items-center",
    "justify-center","bg-blue-600","hover:bg-blue-700","text-white"
  ],
  plugins: [],
}
