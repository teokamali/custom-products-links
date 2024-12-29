document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".cps-copy-btn").forEach((button) => {
    button.addEventListener("click", () => {
      const link = button.getAttribute("data-link");

      if (navigator.clipboard && navigator.clipboard.writeText) {
        // Use Clipboard API if available
        navigator.clipboard
          .writeText(link)
          .then(() => {
            alert("Link copied to clipboard!");
          })
          .catch((err) => {
            console.error("Clipboard API failed: ", err);
          });
      } else {
        // Fallback for older browsers
        const tempInput = document.createElement("textarea");
        tempInput.value = link;
        document.body.appendChild(tempInput);
        tempInput.select();
        try {
          document.execCommand("copy");
          alert("Link copied to clipboard!");
        } catch (err) {
          console.error("Fallback copy failed: ", err);
        }
        document.body.removeChild(tempInput);
      }
    });
  });
});
