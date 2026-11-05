import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.data("reviewModal", (reservationId) => ({
  reservationId,
  open: false,
  rating: 0,
  ratingLabels: ["Pilih rating", "Sangat buruk", "Kurang", "Cukup", "Baik", "Sangat baik"],
  availableTags: ["Bersih", "Nyaman", "Staf ramah", "Lokasi strategis", "Sarapan enak", "Sebanding harga"],
  tags: [],
  aspectRatings: { cleanliness: 0, comfort: 0, location: 0, service: 0 },
  comment: "",
  photos: [],
  videos: [],
  error: "",

  toggleTag(tag) {
    this.tags = this.tags.includes(tag)
      ? this.tags.filter((selected) => selected !== tag)
      : [...this.tags, tag];
  },

  setFiles(kind, fileList) {
    const maxFiles = kind === "photos" ? 5 : 1;
    const otherCount = kind === "photos" ? this.videos.length : this.photos.length;
    const files = [...fileList];
    if (files.length > maxFiles || files.length + otherCount > 6) {
      this.error = "Unggah maksimal 5 foto dan 1 video (total 6 file).";
    } else {
      this.error = "";
    }

    const accepted = files.slice(0, Math.min(maxFiles, 6 - otherCount));
    const input = this.$refs[kind === "photos" ? "photoInput" : "videoInput"];
    const transfer = new DataTransfer();
    accepted.forEach((file) => transfer.items.add(file));
    input.files = transfer.files;
    this[kind] = accepted.map((file) => ({
      file,
      name: file.name,
      key: `${file.name}-${file.lastModified}-${crypto.randomUUID()}`,
    }));
  },

  addDroppedFiles(fileList) {
    const dropped = [...fileList];
    for (const [kind, predicate] of [
      ["photos", (file) => file.type.startsWith("image/")],
      ["videos", (file) => file.type.startsWith("video/")],
    ]) {
      const additions = dropped.filter(predicate);
      if (!additions.length) continue;
      const input = this.$refs[kind === "photos" ? "photoInput" : "videoInput"];
      const transfer = new DataTransfer();
      [...this[kind].map((item) => item.file), ...additions].forEach((file) => transfer.items.add(file));
      this.setFiles(kind, transfer.files);
    }
  },

  removeFile(kind, index) {
    this[kind].splice(index, 1);
    const input = this.$refs[kind === "photos" ? "photoInput" : "videoInput"];
    const transfer = new DataTransfer();
    this[kind].forEach((item) => transfer.items.add(item.file));
    input.files = transfer.files;
    this.error = "";
  },
}));

Alpine.start();

const menuButton = document.querySelector("[data-menu-toggle]");
const navigation = document.querySelector("[data-navigation]");
const landingHeader = document.querySelector(".landing-header");

if (landingHeader) {
  const updateHeaderOnScroll = () => {
    landingHeader.classList.toggle("is-scrolled", window.scrollY > 24);
  };

  updateHeaderOnScroll();
  window.addEventListener("scroll", updateHeaderOnScroll, { passive: true });
}

const themeToggles = document.querySelectorAll("[data-theme-toggle]");
const updateThemeButtons = () => {
  const isDark = document.documentElement.classList.contains("dark");
  const locale = document.documentElement.lang;
  themeToggles.forEach((button) => {
    button.setAttribute("aria-pressed", String(isDark));
    button.setAttribute(
      "aria-label",
      locale === "en"
        ? isDark ? "Switch to light mode" : "Switch to dark mode"
        : isDark ? "Aktifkan mode terang" : "Aktifkan mode gelap",
    );
  });
};

themeToggles.forEach((button) => {
  button.addEventListener("click", () => {
    const isDark = document.documentElement.classList.toggle("dark");
    try {
      localStorage.setItem("stayvia-theme", isDark ? "dark" : "light");
    } catch (error) {
      console.error("Preferensi tema tidak dapat disimpan.", error);
    }
    updateThemeButtons();
  });
});
updateThemeButtons();

if (navigation) {
  const sectionLinks = [...navigation.querySelectorAll('a[href^="#"]')];
  const sectionLinksById = new Map(
    sectionLinks.map((link) => [link.hash.slice(1), link]),
  );
  const observedSections = [...sectionLinksById.keys()]
    .map((id) => document.getElementById(id))
    .filter(Boolean);
  const results = document.querySelector("#hasil");
  let pendingSectionId = null;

  const setCurrentLink = (id) => {
    const link = sectionLinksById.get(id);

    sectionLinks.forEach((sectionLink) => {
      const isCurrent = sectionLink === link;
      sectionLink.classList.toggle("is-current", isCurrent);
      if (isCurrent) {
        sectionLink.setAttribute("aria-current", "location");
      } else {
        sectionLink.removeAttribute("aria-current");
      }
    });
  };

  sectionLinks.forEach((link) => {
    link.addEventListener("click", () => {
      pendingSectionId = link.hash.slice(1);
      setCurrentLink(pendingSectionId);
    });
  });

  if (results) {
    observedSections.push(results);
  }

  if ("IntersectionObserver" in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        const activeEntries = entries.filter((entry) => entry.isIntersecting);
        if (!activeEntries.length) {
          return;
        }

        const pendingEntry = activeEntries.find(
          (entry) => entry.target.id === pendingSectionId,
        );
        if (pendingEntry) {
          pendingSectionId = null;
          setCurrentLink(pendingEntry.target.id);
          return;
        }

        if (pendingSectionId) {
          return;
        }

        const currentEntry = activeEntries.sort(
          (first, second) =>
            Math.abs(first.boundingClientRect.top) -
            Math.abs(second.boundingClientRect.top),
        )[0];
        setCurrentLink(currentEntry.target.id === "hasil" ? "koleksi" : currentEntry.target.id);
      },
      { rootMargin: "-80px 0px -65% 0px", threshold: 0 },
    );

    observedSections.forEach((section) => observer.observe(section));
  }

  const initialSectionId = window.location.hash.slice(1);
  if (sectionLinksById.has(initialSectionId)) {
    setCurrentLink(initialSectionId);
  }
}

if (menuButton && navigation) {
  menuButton.addEventListener("click", () => {
    const isOpen = navigation.classList.toggle("open");
    menuButton.setAttribute("aria-expanded", String(isOpen));
  });

  navigation.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
      navigation.classList.remove("open");
      menuButton.setAttribute("aria-expanded", "false");
    });
  });
}

const searchDestination = document.querySelector("[data-search-destination]");

document.querySelectorAll("[data-back-to-top]").forEach((button) => {
  button.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
});

if (searchDestination) {
  const searchForm = searchDestination.form;

  searchDestination.addEventListener("keyup", (event) => {
    if (event.key === "Enter") {
      searchForm.requestSubmit();
    }
  });
}

const searchGuests = document.querySelector("[data-search-guests]");

if (searchGuests instanceof HTMLSelectElement) {
  const searchForm = searchGuests.form;

  if (searchForm) {
    searchGuests.addEventListener("change", () => searchForm.requestSubmit());
  }
}

document.querySelectorAll("[data-favorite]").forEach((button) => {
  button.addEventListener("click", () => {
    const isFavorite = button.getAttribute("aria-pressed") !== "true";
    button.setAttribute("aria-pressed", String(isFavorite));
  });
});

if (document.querySelector("#hasil") && (!window.location.hash || window.location.hash === "#hasil")) {
  document.querySelector("#hasil").scrollIntoView({ behavior: "smooth" });
}

const selection = document.querySelector("[data-room-selector]");

if (selection) {
  const form = selection.querySelector("[data-room-form]");
  const rooms = [...selection.querySelectorAll("[data-room-option]")];
  const selectedInputs = selection.querySelector("[data-selected-inputs]");
  const feedback = selection.querySelector("[data-selection-feedback]");
  const submitButton = selection.querySelector("[data-selection-submit]");
  const countdown = selection.querySelector("[data-selection-countdown]");
  const countdownTime = countdown.querySelector("[data-countdown-time]");
  const countdownLabel = countdown.querySelector("[data-countdown-label]");
  const progress = countdown.querySelector("[data-countdown-progress]");
  const roomCount = Number(selection.dataset.roomCount);
  const price = Number(selection.dataset.roomPrice);
  const nights = Number(selection.dataset.nights);
  const token = document.querySelector('meta[name="csrf-token"]').content;
  let selected = JSON.parse(selection.dataset.initialRooms);
  let expiry = selection.dataset.selectionExpiry || null;
  let requestPending = false;
  let expired = false;
  let expiryNotified = false;

  const updateSummary = () => {
    const selectedRooms = rooms.filter((room) => selected.includes(Number(room.dataset.number)));
    const floorLabels = [...new Set(selectedRooms.map((room) => room.dataset.floor))].sort();
    selectedInputs.replaceChildren(...selected.map((number) => {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "room_numbers[]";
      input.value = String(number);
      return input;
    }));
    selection.querySelector("[data-summary-rooms]").textContent =
      selectedRooms.length ? selectedRooms.map((room) => room.dataset.label).join(", ") : "-";
    selection.querySelector("[data-summary-floor]").textContent =
      floorLabels.length ? floorLabels.join(", ") : "-";
    selection.querySelector("[data-summary-total]").textContent =
      `Rp${new Intl.NumberFormat("id-ID").format(price * nights * selectedRooms.length)}`;
    submitButton.disabled = selectedRooms.length !== roomCount || requestPending || expired;
  };

  const renderRooms = () => {
    for (const room of rooms) {
      const number = Number(room.dataset.number);
      const isSelected = selected.includes(number);
      const status = isSelected ? "selected" : room.dataset.baseStatus;
      room.classList.remove("is-selected", "is-available");
      room.classList.add(`is-${status}`);
      room.dataset.status = status;
      room.setAttribute("aria-pressed", String(isSelected));
      room.disabled = expired || requestPending || (!isSelected && ["occupied", "locked"].includes(status));
      const statusLabels = {
        available: "tersedia",
        selected: "dipilih",
        occupied: "terisi",
        locked: "dikunci sementara",
      };
      room.setAttribute("aria-label", `Kamar ${room.dataset.label}, ${statusLabels[status]}`);
      let stateIndicator = room.querySelector(".room-tile-state");
      if (["occupied", "locked"].includes(status)) {
        if (!stateIndicator) {
          stateIndicator = document.createElement("span");
          stateIndicator.className = "room-tile-state";
          stateIndicator.setAttribute("aria-hidden", "true");
          room.append(stateIndicator);
        }
        stateIndicator.textContent = status === "occupied" ? "×" : "⌑";
      } else if (stateIndicator) {
        stateIndicator.remove();
      }
    }
    updateSummary();
  };

  const showMessage = (message, isError = false) => {
    feedback.textContent = message;
    feedback.classList.toggle("is-error", isError);
  };

  const updateCountdown = () => {
    if (!expiry) {
      countdownTime.textContent = "";
      progress.style.setProperty("--countdown-progress", "0%");
      return;
    }
    const remaining = Math.max(0, Math.ceil((Date.parse(expiry) - Date.now()) / 1000));
    const minutes = String(Math.floor(remaining / 60)).padStart(2, "0");
    const seconds = String(remaining % 60).padStart(2, "0");
    countdownTime.textContent = `${minutes}:${seconds}`;
    progress.style.setProperty("--countdown-progress", `${Math.min(100, remaining / 600 * 100)}%`);
    countdown.classList.toggle("is-urgent", remaining <= 120);

    if (remaining === 0 && !expiryNotified) {
      expiryNotified = true;
      expired = true;
      selected = [];
      renderRooms();
      countdownLabel.textContent = "Waktu pemilihan habis";
      showMessage("Waktu pemilihan habis. Pilih kamar kembali untuk memulai penguncian baru.", true);
      expiry = null;
      requestPending = true;
      fetch(selection.dataset.lockUrl, {
        method: "POST",
        keepalive: true,
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-TOKEN": token,
        },
        body: JSON.stringify({
          checkin: selection.dataset.checkin,
          checkout: selection.dataset.checkout,
          rooms: roomCount,
          room_numbers: [],
        }),
      }).catch(() => {
        showMessage("Waktu penguncian habis. Muat ulang halaman untuk memperbarui ketersediaan kamar.", true);
      }).finally(() => {
        requestPending = false;
        expired = false;
        renderRooms();
      });
    }
  };

  const setSelection = async (nextSelection) => {
    requestPending = true;
    submitButton.disabled = true;
    showMessage("Menyimpan pilihan kamar…");
    renderRooms();

    try {
      const response = await fetch(selection.dataset.lockUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          "X-CSRF-TOKEN": token,
        },
        body: JSON.stringify({
          checkin: selection.dataset.checkin,
          checkout: selection.dataset.checkout,
          rooms: roomCount,
          room_numbers: nextSelection,
        }),
      });
      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.errors?.room_numbers?.[0] || "Kamar tidak dapat dikunci. Silakan coba lagi.");
      }

      selected = result.room_numbers.map(Number);
      expiry = result.expires_at;
      selection.dataset.selectionExpiry = expiry || "";
      expired = false;
      expiryNotified = false;
      countdownLabel.textContent = selected.length ? "Kamar dikunci sementara" : "Pilih kamar untuk mengunci 10 menit";
      showMessage(selected.length ? "Pilihan tersimpan. Selesaikan pemesanan sebelum waktu habis." : "");
      renderRooms();
      updateCountdown();
    } catch (error) {
      showMessage(error.message, true);
      renderRooms();
    } finally {
      requestPending = false;
      renderRooms();
    }
  };

  for (const room of rooms) {
    room.addEventListener("click", () => {
      const number = Number(room.dataset.number);
      if (requestPending || expired) return;
      if (selected.includes(number)) {
        setSelection(selected.filter((selectedNumber) => selectedNumber !== number));
      } else if (selected.length >= roomCount) {
        if (roomCount === 1) {
          setSelection([number]);
        } else {
          showMessage(`Anda hanya dapat memilih ${roomCount} kamar. Hapus pilihan kamar terlebih dahulu untuk menggantinya.`, true);
        }
      } else {
        setSelection([...selected, number]);
      }
    });
  }

  selection.querySelectorAll("[data-floor-tab]").forEach((tab) => {
    tab.addEventListener("click", () => {
      selection.querySelectorAll("[data-floor-tab]").forEach((item) => {
        const active = item === tab;
        item.classList.toggle("is-active", active);
        item.setAttribute("aria-selected", String(active));
      });
      selection.querySelectorAll("[data-floor-panel]").forEach((panel) => {
        const active = panel.dataset.floorPanel === tab.dataset.floorTab;
        panel.classList.toggle("is-active", active);
        panel.hidden = !active;
      });
    });
  });

  form.addEventListener("submit", (event) => {
    if (selected.length !== roomCount || requestPending || expired) {
      event.preventDefault();
      showMessage("Pilih dan kunci jumlah kamar sesuai pesanan sebelum melanjutkan.", true);
    }
  });

  const countdownTimer = window.setInterval(() => {
    updateCountdown();
    if (expired) window.clearInterval(countdownTimer);
  }, 1000);
  renderRooms();
  updateCountdown();

  const refreshAvailability = async () => {
    try {
      const params = new URLSearchParams({
        checkin: selection.dataset.checkin,
        checkout: selection.dataset.checkout,
      });
      const response = await fetch(`${selection.dataset.availabilityUrl}?${params}`, {
        headers: { "Accept": "application/json" },
      });
      if (!response.ok) return;
      const result = await response.json();
      const updatedStates = new Map(result.rooms.map((room) => [Number(room.number), room.status]));
      for (const room of rooms) {
        const status = updatedStates.get(Number(room.dataset.number));
        if (!status) continue;
        room.dataset.baseStatus = status === "selected" ? "available" : status;
        if (selected.includes(Number(room.dataset.number)) && status !== "selected") {
          selected = selected.filter((number) => number !== Number(room.dataset.number));
          showMessage(`Kamar ${room.dataset.label} sudah tidak terkunci. Silakan pilih kamar yang tersedia.`, true);
        }
      }
      if (result.selection_expiry) {
        expiry = result.selection_expiry;
        selection.dataset.selectionExpiry = expiry;
      }
      renderRooms();
    } catch {
      showMessage("Status ketersediaan belum dapat diperbarui. Pilihan Anda tetap tersimpan.", true);
    }
  };
  window.setInterval(refreshAvailability, 15000);
}

document.querySelectorAll("[data-selection-expiry]").forEach((selectionTimer) => {
  const expiry = selectionTimer.dataset.selectionExpiry;
  const time = selectionTimer.querySelector("[data-countdown-time]");
  const progress = selectionTimer.querySelector("[data-countdown-progress]");
  const notice = selectionTimer.querySelector(".guest-lock-notice") || selectionTimer;
  const submit = selectionTimer.querySelector("[data-guest-submit]");
  const expiredNotices = selectionTimer.querySelectorAll("[data-guest-expired]");
  if (!expiry || !time || !progress) return;

  const updateGuestCountdown = () => {
    const remaining = Math.max(0, Math.ceil((Date.parse(expiry) - Date.now()) / 1000));
    time.textContent = `${String(Math.floor(remaining / 60)).padStart(2, "0")}:${String(remaining % 60).padStart(2, "0")}`;
    progress.style.setProperty("--countdown-progress", `${Math.min(100, remaining / 600 * 100)}%`);
    notice.classList.toggle("is-urgent", remaining <= 120);
    if (remaining === 0) {
      if (submit) submit.disabled = true;
      expiredNotices.forEach((notice) => {
        notice.hidden = false;
      });
      window.clearInterval(timer);
    }
  };

  const timer = window.setInterval(updateGuestCountdown, 1000);
  updateGuestCountdown();
});

document.querySelectorAll("[data-copy-payment]").forEach((button) => {
  button.addEventListener("click", async () => {
    const feedback = button.closest(".payment-issued-details, .payment-instructions")
      ?.querySelector("[data-copy-feedback]") || document.querySelector("[data-copy-feedback]");
    try {
      await navigator.clipboard.writeText(button.dataset.copyPayment);
      if (feedback) feedback.textContent = "Nomor pembayaran berhasil disalin.";
    } catch {
      if (feedback) feedback.textContent = "Gagal menyalin. Silakan pilih dan salin nomor secara manual.";
    }
  });
});

document.querySelectorAll("[data-payment-selector]").forEach((form) => {
  const options = [...form.querySelectorAll("[data-payment-method]")];
  const details = form.querySelector("[data-selected-method-details]");
  const label = form.querySelector("[data-selected-method-label]");
  const recipient = form.querySelector("[data-selected-method-recipient]");
  const accountName = form.querySelector("[data-selected-method-account-name]");
  const copyButton = form.querySelector("[data-copy-selected-payment]");
  const recipientContainer = form.querySelector("[data-selected-method-recipient-container]");
  const accountContainer = form.querySelector("[data-selected-method-account-container]");
  const qrContainer = form.querySelector("[data-selected-method-qr-container]");
  const qrImage = form.querySelector("[data-selected-method-qr]");
  const senderBankName = form.querySelector("[data-sender-bank-name]");
  let previousMethodIsQris = Boolean(options.find((option) => option.checked)?.dataset.methodQrImage);

  const updateSelectedMethod = () => {
    const selected = options.find((option) => option.checked && !option.disabled);
    form.querySelectorAll(".payment-method-option").forEach((option) => {
      option.classList.toggle("is-selected", option.querySelector("[data-payment-method]") === selected);
    });

    if (!selected) {
      if (details) details.hidden = true;
      if (copyButton) copyButton.disabled = true;
      if (recipientContainer) recipientContainer.hidden = true;
      if (accountContainer) accountContainer.hidden = true;
      if (qrContainer) qrContainer.hidden = true;
      return;
    }

    if (details) details.hidden = false;
    if (label) label.textContent = selected.dataset.methodLabel;
    const isQris = Boolean(selected.dataset.methodQrImage);
    if (senderBankName) {
      senderBankName.readOnly = !isQris;
      if (!isQris) senderBankName.value = selected.dataset.methodBankName || selected.dataset.methodLabel || "";
    }
    if (recipient) recipient.textContent = selected.dataset.methodRecipient;
    if (accountName) accountName.textContent = selected.dataset.methodAccountName;
    if (recipientContainer) recipientContainer.hidden = isQris;
    if (accountContainer) accountContainer.hidden = !selected.dataset.methodAccountName;
    if (qrContainer) qrContainer.hidden = !isQris;
    if (qrImage && isQris) qrImage.src = selected.dataset.methodQrImage;
    if (copyButton) {
      copyButton.dataset.copyPayment = selected.dataset.methodRecipient;
      copyButton.disabled = isQris;
    }
  };

  options.forEach((option) => option.addEventListener("change", () => {
    const selected = options.find((item) => item.checked && !item.disabled);
    const selectedIsQris = Boolean(selected?.dataset.methodQrImage);
    if (senderBankName && selectedIsQris && !previousMethodIsQris) {
      senderBankName.value = "";
    }
    previousMethodIsQris = selectedIsQris;
    updateSelectedMethod();
  }));
  updateSelectedMethod();
});

document.querySelectorAll("[data-payment-countdown]").forEach((countdown) => {
  const expiry = Date.parse(countdown.dataset.paymentExpiry);
  const output = countdown.querySelector("[data-payment-countdown-time]");
  if (!Number.isFinite(expiry) || !output) return;

  const update = () => {
    const seconds = Math.max(0, Math.ceil((expiry - Date.now()) / 1000));
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;
    output.textContent = days > 0
      ? `Sisa waktu ${days}h ${String(hours).padStart(2, "0")}j ${String(minutes).padStart(2, "0")}m`
      : `Sisa waktu ${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}:${String(remainingSeconds).padStart(2, "0")}`;
    countdown.classList.toggle("is-urgent", seconds <= 1800);
  };

  update();
  window.setInterval(update, 1000);
});

document.querySelectorAll("[data-payment-proof-file]").forEach((input) => {
  const dropzone = input.closest(".payment-upload-dropzone");
  const fileName = dropzone?.querySelector("[data-payment-file-name]");
  if (!dropzone || !fileName) return;

  const showFileName = (file) => {
    fileName.textContent = file ? file.name : "Pilih atau jatuhkan file bukti di sini";
    dropzone.classList.toggle("has-file", Boolean(file));
  };

  input.addEventListener("change", () => showFileName(input.files?.[0]));
  ["dragenter", "dragover"].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.add("is-dragging");
    });
  });
  ["dragleave", "drop"].forEach((eventName) => {
    dropzone.addEventListener(eventName, (event) => {
      event.preventDefault();
      dropzone.classList.remove("is-dragging");
    });
  });
  dropzone.addEventListener("drop", (event) => {
    const [file] = event.dataTransfer?.files || [];
    if (!file) return;
    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
    showFileName(file);
  });
});

document.querySelectorAll("[data-copy-selected-payment]").forEach((button) => {
  button.addEventListener("click", async () => {
    const feedback = document.querySelector("[data-copy-feedback]");
    try {
      await navigator.clipboard.writeText(button.dataset.copyPayment);
      if (feedback) feedback.textContent = "Nomor pembayaran berhasil disalin.";
    } catch {
      if (feedback) feedback.textContent = "Gagal menyalin. Silakan pilih dan salin nomor secara manual.";
    }
  });
});

const bookingDeleteDialog = document.querySelector("[data-booking-delete-dialog]");

if (bookingDeleteDialog instanceof HTMLDialogElement) {
  const bookingDeleteForm = bookingDeleteDialog.querySelector("[data-booking-delete-form]");
  const bookingDeleteLabel = bookingDeleteDialog.querySelector("[data-booking-delete-label]");
  const cancelButton = bookingDeleteDialog.querySelector("[data-booking-delete-cancel]");

  document.querySelectorAll("[data-booking-delete-trigger]").forEach((trigger) => {
    trigger.addEventListener("click", () => {
      if (!(bookingDeleteForm instanceof HTMLFormElement) || !bookingDeleteLabel) return;
      bookingDeleteForm.action = trigger.dataset.deleteUrl;
      bookingDeleteLabel.textContent = trigger.dataset.bookingLabel;
      bookingDeleteDialog.showModal();
    });
  });

  cancelButton?.addEventListener("click", () => bookingDeleteDialog.close());
  bookingDeleteDialog.addEventListener("click", (event) => {
    if (event.target === bookingDeleteDialog) bookingDeleteDialog.close();
  });
}

const bookingCancelDialog = document.querySelector("[data-booking-cancel-dialog]");

if (bookingCancelDialog instanceof HTMLDialogElement) {
  const bookingCancelForm = bookingCancelDialog.querySelector("[data-booking-cancel-form]");
  const bookingCancelLabel = bookingCancelDialog.querySelector("[data-booking-cancel-label]");
  const acknowledgement = bookingCancelDialog.querySelector("[data-booking-cancel-ack]");
  const submitButton = bookingCancelDialog.querySelector("[data-booking-cancel-submit]");
  const otherReason = bookingCancelDialog.querySelector("[data-booking-other-reason]");
  const otherReasonInput = bookingCancelDialog.querySelector("[data-booking-other-reason-input]");

  const updateOtherReason = () => {
    if (!(otherReason instanceof HTMLElement) || !(otherReasonInput instanceof HTMLTextAreaElement)) return;
    const isOtherReasonSelected = bookingCancelForm instanceof HTMLFormElement
      && bookingCancelForm.querySelector('input[name="reason"]:checked')?.value === "Alasan lain";
    otherReason.hidden = !isOtherReasonSelected;
    otherReasonInput.disabled = !isOtherReasonSelected;
    otherReasonInput.required = isOtherReasonSelected;
  };

  document.querySelectorAll("[data-booking-cancel-trigger]").forEach((trigger) => {
    trigger.addEventListener("click", () => {
      if (
        !(bookingCancelForm instanceof HTMLFormElement) ||
        !(bookingCancelLabel instanceof HTMLElement) ||
        !(submitButton instanceof HTMLButtonElement)
      ) return;

      bookingCancelForm.reset();
      updateOtherReason();
      submitButton.disabled = true;
      bookingCancelForm.action = trigger.dataset.cancelUrl;
      bookingCancelLabel.textContent = trigger.dataset.bookingLabel || "Reservasi ini";
      bookingCancelDialog.showModal();
    });
  });

  bookingCancelForm?.querySelectorAll('input[name="reason"]').forEach((input) => {
    input.addEventListener("change", updateOtherReason);
  });

  if (acknowledgement instanceof HTMLInputElement && submitButton instanceof HTMLButtonElement) {
    acknowledgement.addEventListener("change", () => {
      submitButton.disabled = !acknowledgement.checked;
    });
  }

  bookingCancelDialog.querySelector("[data-booking-cancel-close]")?.addEventListener("click", () => {
    bookingCancelDialog.close();
  });
  bookingCancelDialog.addEventListener("click", (event) => {
    if (event.target === bookingCancelDialog) bookingCancelDialog.close();
  });
}

document.querySelectorAll("[data-password-toggle]").forEach((toggle) => {
  if (!(toggle instanceof HTMLButtonElement)) return;

  const input = toggle.closest(".customer-login-input-wrap")?.querySelector("input");
  if (!(input instanceof HTMLInputElement)) return;

  toggle.addEventListener("click", () => {
    const isVisible = input.type === "text";
    input.type = isVisible ? "password" : "text";
    toggle.setAttribute("aria-pressed", String(!isVisible));
    toggle.setAttribute("aria-label", isVisible ? "Tampilkan kata sandi" : "Sembunyikan kata sandi");
  });
});
