const faqQuestions = document.querySelectorAll(".faq-question");

faqQuestions.forEach(function(question) {

    question.addEventListener("click", function() {

        const item = this.closest(".faq-item");
        const answer = item.querySelector(".faq-answer");

        // Close all other FAQ items.
        document.querySelectorAll(".faq-item").forEach(function(otherItem) {

            if (otherItem !== item) {

                otherItem.classList.remove("active");

                const otherQuestion =
                    otherItem.querySelector(".faq-question");

                const otherAnswer =
                    otherItem.querySelector(".faq-answer");

                otherQuestion.setAttribute("aria-expanded", "false");

                otherAnswer.style.maxHeight = null;

            }

        });

        // Open or close the selected FAQ.
        const isOpen = item.classList.toggle("active");

        question.setAttribute("aria-expanded", String(isOpen));

        if (isOpen) {

            answer.style.maxHeight = answer.scrollHeight + "px";

        } else {

            answer.style.maxHeight = null;

        }

    });

});