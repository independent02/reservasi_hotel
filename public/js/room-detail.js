const roomDetail = document.querySelector(".rd-page");
const roomBackLink = document.querySelector(".room-detail-topbar .flow-topbar-action");

if (roomDetail && roomBackLink) {
  const backUrl = roomDetail.dataset.backUrl;
  if (backUrl) {
    roomBackLink.href = backUrl;
  }
}

const dialog = document.querySelector("[data-cancel-dialog]");
const openDialogButton = document.querySelector("[data-open-cancel-dialog]");
const closeDialogButtons = document.querySelectorAll("[data-close-cancel-dialog]");
const acknowledgement = document.querySelector("[data-cancel-ack]");
const submitButton = document.querySelector("[data-cancel-submit]");
const roomCancelForm = document.querySelector("[data-cancel-form]");
const otherReason = document.querySelector("[data-room-other-reason]");
const otherReasonInput = document.querySelector("[data-room-other-reason-input]");

const updateRoomOtherReason = () => {
  if (
    !(roomCancelForm instanceof HTMLFormElement) ||
    !(otherReason instanceof HTMLElement) ||
    !(otherReasonInput instanceof HTMLTextAreaElement)
  ) return;

  const isOtherReasonSelected = roomCancelForm.querySelector('input[name="reason"]:checked')?.value === "Alasan lain";
  otherReason.hidden = !isOtherReasonSelected;
  otherReasonInput.disabled = !isOtherReasonSelected;
  otherReasonInput.required = isOtherReasonSelected;
};

roomCancelForm?.querySelectorAll('input[name="reason"]').forEach((input) => {
  input.addEventListener("change", updateRoomOtherReason);
});

if (dialog instanceof HTMLDialogElement && openDialogButton instanceof HTMLButtonElement) {
  openDialogButton.addEventListener("click", () => {
    roomCancelForm?.reset();
    updateRoomOtherReason();
    if (submitButton instanceof HTMLButtonElement) submitButton.disabled = true;
    dialog.showModal();
  });

  closeDialogButtons.forEach((button) => {
    button.addEventListener("click", () => dialog.close());
  });

  dialog.addEventListener("click", (event) => {
    if (event.target === dialog) {
      dialog.close();
    }
  });

  if (acknowledgement instanceof HTMLInputElement && submitButton instanceof HTMLButtonElement) {
    acknowledgement.addEventListener("change", () => {
      submitButton.disabled = !acknowledgement.checked;
    });
  }
}

const tiltCard = document.querySelector("[data-room-tilt]");
const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)");

if (tiltCard instanceof HTMLElement && window.matchMedia("(min-width: 901px)").matches && !reducedMotion.matches) {
  tiltCard.addEventListener("pointermove", (event) => {
    const bounds = tiltCard.getBoundingClientRect();
    const x = (event.clientX - bounds.left) / bounds.width - 0.5;
    const y = (event.clientY - bounds.top) / bounds.height - 0.5;
    tiltCard.style.transform = `perspective(1100px) rotateY(${x * 3}deg) rotateX(${-y * 2}deg) translateY(-2px)`;
  });

  tiltCard.addEventListener("pointerleave", () => {
    tiltCard.style.transform = "";
  });
}
