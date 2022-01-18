document.addEventListener("DOMContentLoaded", () => {
    let form = document.getElementById("application");
    let verifyUnload = (e) => {
        for (let input of document.getElementsByTagName("input")) {
            if (input.type === "checkbox" || input.type === "radio") {
                continue;
            }
            if (input.value !== input.defaultValue) {
                console.log(input);
                console.log(input.value);
                console.log(input.defaultValue);
                e.preventDefault();
                e.returnValue = true;
                return;
            }
        }
    };
    window.addEventListener("beforeunload", verifyUnload);
    form.addEventListener("submit", () => window.removeEventListener("beforeunload", verifyUnload));
});

window.addEventListener("keypress", (e) => {
    if (e.key === "Enter" && e.target.nodeName === "INPUT" && e.target.type !== "textarea" && e.target.type !==
        "submit" && e.target.type !== "button" && e.target.type !== "file") {
        e.preventDefault();
    }
});
