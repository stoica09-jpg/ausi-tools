(async () => {

    //MARK: CONFIG
    const baseUrl = `${window.location.origin}/ausi`;
    const mainNodeUrl = `${baseUrl}/ilmapp/ajaxloadmain`;
    const subNodeUrl = `${baseUrl}/Ilmapp/ajax`;
    const leafListUrl = `${baseUrl}/ilmapp/ajaxedit`;
    const leafEditUrl = `${baseUrl}/ilmapp/ajaxupdate`;
    const siiirCodeEditUrl =
        `${baseUrl}/Ilmapp/validateschool`; //note the big I
    const mainNodeParam =
        "operation=main&businessCategorySearch=undefined&sn=undefined&nameSearch=undefined&dn=undefined&s_tel=undefined";
    const stRegex = />(st=[^<]*)/g;
    const localityRegex = />(l=[^<]*)/g;
    const ouRegex = />(ou=[^<]*)/g;
    const ouSiiirNameRegex = /Denumire: <\/span>([^<]*)</;
    const cnRegex = />(cn=[^<]*)/g;
    const csvHeader = [
        "ID",
        "Cod SIIIR",
        "Unitate SIIIR",
        "Centru",
        "Nume",
        "Telefon",
        "Email",
        "CNP",
        "CI",
        "Domiciliu",
        "bacActiv",
        "bacMatern",
        "bacSpecial",
        "en8Activ",
        "en8Matern",
        "en8Special"
    ];
    //MARK: HELPER FUNCTIONS
    const responseTextOrThrow = async (response, action) => {
        const responseText = await response.text();
        if (!response.ok) {
            throw new Error(`${action} a eșuat (HTTP ${response.status}).`);
        }
        return responseText;
    }

    const getNode = async (param) => {
        const response = await fetch(mainNodeUrl, {
            method: "post",
            headers: {
                "Accept": "text/html, */*",
                "Content-type": "application/x-www-form-urlencoded; charset=UTF-8"
            },
            body: param
        });
        return await responseTextOrThrow(response, "Încărcarea listei principale");
    }

    const getSubNode = async (param) => {
        const response = await fetch(subNodeUrl, {
            method: "post",
            headers: {
                "Accept": "text/html, */*",
                "Content-type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: param
        });
        return await responseTextOrThrow(response, "Încărcarea sublistei");
    }

    const getLeafList = async (param) => {
        const response = await fetch(leafListUrl, {
            method: "post",
            headers: {
                "Accept": "text/html, */*",
                "Content-type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: param
        });
        return await responseTextOrThrow(response, "Încărcarea detaliilor");
    }

    const displayMessage = (message) => {
        displayMessageWithSeverity(message, "primary");
    }

    const displayError = (message) => {
        displayMessageWithSeverity(message, "danger");
    }

    const displayWarning = (message) => {
        displayMessageWithSeverity(message, "warning");
    }

    const displaySuccess = (message) => {
        displayMessageWithSeverity(message, "success");
    }

    const displayMessageWithSeverity = (message, severity) => {
        const messageDiv = document.getElementById("message");
        if (!messageDiv) {
            console.warn(message);
            return;
        }
        messageDiv.className = `alert alert-${severity}`;
        messageDiv.innerText = message;
    }

    const is = (variable) => {
        return !(typeof variable === 'undefined');
    }

    const toCellString = (value) => {
        if (value == null) {
            return "";
        }
        return String(value).trim();
    }

    //Excel drops the leading 0 of phone numbers typed as numbers
    const toPhoneString = (value) => {
        const phone = toCellString(value);
        if (typeof value === "number" && /^[1-9]\d{8}$/.test(phone)) {
            return `0${phone}`;
        }
        return phone;
    }

    const decodeHtml = (text) => {
        return new DOMParser().parseFromString(`<!doctype html><body>${text}`, "text/html").body.textContent;
    }

    const normalizeImportRow = (row) => {
        if (!Array.isArray(row)) {
            return [];
        }
        return row.map(toCellString);
    }

    const isDaCell = (value) => toCellString(value).toUpperCase() === "DA";

    const makeParamInternal = (prefix, st, l, ou, cn) => {
        let dn = "";
        if (cn) dn += `${cn},`;
        if (ou) dn += `${ou},`;
        if (l) dn += `${l},`;
        if (st) dn += st;

        const params = new URLSearchParams();
        params.set(prefix.replace("=", ""), dn);
        params.set("del", "");
        params.set("ult", "_ult");
        return params.toString();
    }

    const makeParam = (st, l, ou, cn) => makeParamInternal("dn=", st, l, ou, cn);
    const makeDetailParam = (st, l, ou, cn) => makeParamInternal("edit=", st, l, ou, cn);



    const getReg = (data, regex) => {
        let retVal = "";
        let match = regex.exec(data);
        if (match != null) {
            retVal = match[1];
        }
        return retVal;
    }

    const parseHtml = (data) => {
        return new DOMParser().parseFromString(data, "text/html");
    }

    const getInputValue = (doc, id) => {
        return doc.getElementById(id)?.value || "";
    }

    const getTextareaValue = (doc, id) => {
        return doc.getElementById(id)?.value || "";
    }

    const getSelectedOptionText = (doc) => {
        const selectedOption = Array.from(doc.querySelectorAll("option"))
            .find((option) => option.selected || option.hasAttribute("selected"));
        return toCellString(selectedOption?.textContent);
    }

    const getCheckedValue = (doc, value) => {
        const checked = Array.from(doc.querySelectorAll("input"))
            .some((input) => input.value === value && input.checked);
        return checked ? "DA" : "";
    }

    //returns the matched DN components ("st=...", "l=...", ...) without the leading ">"
    const getMatches = (data, regex) => {
        return (data.match(regex) || []).map((x) => decodeHtml(x.substring(1)).trim());
    }

    let isBusy = false;

    //MARK: SETBUSY
    const setBusy = (busy) => {
        isBusy = busy;
        for (const control of document.querySelectorAll("#ausiToolsForm input")) {
            control.disabled = busy;
        }
        if (busy) {
            document.body.style.cursor = "wait";
            const anchors = document.getElementsByTagName("a");
            for (const anchor of anchors) {
                //disable all links
                anchor.style.pointerEvents = "none";
            }
            //show progress bar
            const progressOuter = document.getElementById("progressOuter");
            if (progressOuter) {
                progressOuter.style.display = "flex";
            }
        } else {
            document.body.style.cursor = "default";
            const anchors = document.getElementsByTagName("a");
            for (const anchor of anchors) {
                //enable all links
                anchor.style.pointerEvents = "auto";
            }
            //hide progress bar
            const progressOuter = document.getElementById("progressOuter");
            if (progressOuter) {
                progressOuter.style.display = "none";
            }
        }
    }

    //MARK: PROGRESS UPDATE
    const updateProgress = (progress) => {
        const progressBarOuterDiv = document.getElementById("progressOuter");
        const progressBarInner = document.getElementById("progressInner");
        if (!progressBarOuterDiv || !progressBarInner) {
            return;
        }

        progressBarOuterDiv.setAttribute("aria-valuenow", progress.toFixed(0));
        progressBarInner.style.width = `${progress.toFixed(0)}%`;
        progressBarInner.textContent = `${progress.toFixed(0)}%`;
        //console.log(progress);
    };

    //MARK: EXPORT
    const doExport = async (event) => {
        if (event) {
            event.preventDefault();
        }
        if (isBusy) {
            return;
        }
        const csvData = [csvHeader];
        let timer = null;
        setBusy(true);
        try {
            displayMessage("Export in curs de desfășurare");
            const mainNodeHtml = await getNode(mainNodeParam);
            const stArray = getMatches(mainNodeHtml, stRegex);
            if (stArray.length === 0) {
                throw new Error("Nu s-au găsit județe. Verificați autentificarea AUSI.");
            }
            const stValues = stArray;

            //set loading indicator
            let recordCounter = 0;
            let totalRecords = stArray.length;
            timer = setInterval(() => {
                updateProgress(recordCounter / totalRecords * 100);
            }, 1000);
            let stLabel = "";
            for (const stValue of stValues) {
                if (stValues.length > 1) {
                    stLabel = stValue;
                }
                const localityParam = makeParam(stValue);
                const localityHtml = await getSubNode(localityParam);
                const localityArray = getMatches(localityHtml, localityRegex);
                totalRecords += localityArray.length;

                //get organizational units (ou)
                for (const currentLocality of localityArray) {
                    displayMessage(`${stLabel} ${currentLocality}`);
                    const ouHtml = await getSubNode(makeParam(stValue, currentLocality));
                    const ouArray = getMatches(ouHtml, ouRegex);
                    totalRecords += ouArray.length;
                    for (const currentOu of ouArray) {
                        const ouDetailResponseText = await getLeafList(makeDetailParam(stValue, currentLocality, currentOu));
                        const ouDetailDoc = parseHtml(ouDetailResponseText);
                        const currentCentru = getSelectedOptionText(ouDetailDoc);
                        const currentSiiirCode = getInputValue(ouDetailDoc, "cod_siiir");
                        const currentSiiirName = decodeHtml(getReg(ouDetailResponseText, ouSiiirNameRegex)).trim();
                        const cnHtml = await getSubNode(makeParam(stValue, currentLocality, currentOu));
                        const cnArray = getMatches(cnHtml, cnRegex);
                        for (const currentCn of cnArray) {
                            const cnDetailResponseText = await getLeafList(makeDetailParam(stValue, currentLocality, currentOu, currentCn));
                            const cnDetailDoc = parseHtml(cnDetailResponseText);
                            const id = `${currentCn},${currentOu},${currentLocality},${stValue}`;
                            console.log(`[${recordCounter}/${totalRecords}]=${id}`);
                            let oneLine = [];
                            oneLine[0] = id;
                            oneLine[1] = currentSiiirCode;
                            oneLine[2] = currentSiiirName;
                            oneLine[3] = currentCentru;
                            oneLine[4] = getInputValue(cnDetailDoc, "sname");
                            oneLine[5] = getInputValue(cnDetailDoc, "telephoneNumber");
                            oneLine[6] = getInputValue(cnDetailDoc, "mail");
                            oneLine[7] = getInputValue(cnDetailDoc, "cnp");
                            oneLine[8] = getInputValue(cnDetailDoc, "serienr");
                            oneLine[9] = getTextareaValue(cnDetailDoc, "domiciliu");
                            oneLine[10] = getCheckedValue(cnDetailDoc, "bacActiv");
                            oneLine[11] = getCheckedValue(cnDetailDoc, "bacMatern");
                            oneLine[12] = getCheckedValue(cnDetailDoc, "bacSpecial");
                            oneLine[13] = getCheckedValue(cnDetailDoc, "capacitateActiv");
                            oneLine[14] = getCheckedValue(cnDetailDoc, "capacitateMatern");
                            oneLine[15] = getCheckedValue(cnDetailDoc, "capacitateSpecial");
                            csvData.push(oneLine);
                        }
                        recordCounter++;
                    }
                    recordCounter++;
                }
            }
            updateProgress(100);

            //save xlsx
            let worksheet = XLSX.utils.aoa_to_sheet(csvData);
            let workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1");
            XLSX.writeFile(workbook, "export_ausi.xlsx");
            displaySuccess("Export finalizat");
        } catch (err) {
            displayError(`Export eșuat: ${err.message}`);
        } finally {
            if (timer) {
                clearInterval(timer);
            }
            setBusy(false);
        }
    }

    //MARK: IMPORT
    const doImport = (event) => {
        const file = event.target.files[0];
        if (!file || isBusy) {
            event.target.value = null;
            return;
        }

        //show native browser confirmation dialog
        const confirmation = window.confirm("Doriți să importați fișierul?");
        if (!confirmation) {
            event.target.value = null;
            return;
        }

        let doSIIIRSync = false;
        const siiirSyncCheckbox = document.getElementById("siiirSync");
        if (siiirSyncCheckbox && siiirSyncCheckbox.checked) {
            doSIIIRSync = true;
        }
        setBusy(true);
        displayMessage("Import in curs de desfășurare");
        const reader = new FileReader();
        reader.onload = async (e) => {
            let timer = null;
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, {
                    type: "array"
                });
                const sheetName = workbook.SheetNames[0];
                if (!sheetName) {
                    throw new Error("Fișierul nu conține foi de calcul.");
                }
                let importArray = XLSX.utils.sheet_to_json(workbook.Sheets[sheetName], {
                    header: 1,
                    defval: "",
                    raw: true
                });
                let progressCounter = 0;
                let errorcounter = 0;
                let totalRecords = importArray.length || 1;
                timer = setInterval(() => {
                    updateProgress(Math.round((progressCounter / totalRecords * 100)));
                }, 1000);

                let errorLines = [];

                for (const [lineIndex, importItem] of importArray.entries()) {
                    const items = normalizeImportRow(importItem);
                    while (items.length < csvHeader.length) {
                        items.push("");
                    }
                    if (items.every((item) => item === "")) {
                        //skip empty lines
                        progressCounter++;
                        continue;
                    }
                    if (items[0].startsWith("cn=")) {
                        let di = "";
                        let po = "";

                        if (isDaCell(items[10])) {
                            di = "10";
                        } else {
                            di = "00";
                        }

                        if (isDaCell(items[11])) {
                            di = di + "1";
                        } else {
                            di = di + "0";
                        }

                        if (isDaCell(items[12])) {
                            di = di + "1";
                        } else {
                            di = di + "0";
                        }

                        if (isDaCell(items[13])) {
                            po = "10";
                        } else {
                            po = "00";
                        }

                        if (isDaCell(items[14])) {
                            po = po + "1";
                        } else {
                            po = po + "0";
                        }

                        if (isDaCell(items[15])) {
                            po = po + "1";
                        } else {
                            po = po + "0";
                        }

                        let params = {
                            //operation: "contact",
                            edit: items[0],
                            sn: items[4],
                            givenName: "",
                            telephoneNumber: toPhoneString(importItem[5]),
                            mail: items[6] || "mail@example.com",
                            cnp: items[7] || "1234567890123",
                            serienr: items[8] || "CI 123456",
                            domiciliu: items[9] || "Domiciliu",
                            destinationIndicator: di,
                            postOfficeBox: po
                        };

                        const formBody = new URLSearchParams({
                            operation: "contact",
                            ...params
                        }).toString();

                        try {
                            const updateResponse = await fetch(leafEditUrl, {
                                method: "post",
                                credentials: "same-origin",
                                headers: {
                                    Accept: "text/html, */*",
                                    "Content-type":
                                        "application/x-www-form-urlencoded; charset=UTF-8",
                                    "X-Requested-With": "XMLHttpRequest"
                                },
                                body: formBody
                            });
                            await responseTextOrThrow(updateResponse, "Actualizarea persoanei");

                            const ouArray = items[0].split(',');
                            ouArray.splice(0, 1);
                            const ou = ouArray.join(',');

                            let businessCategory = "null";
                            if (items[3] == "Centru de comunicare") {
                                businessCategory = "businessCategory";
                            }

                            //update centru comunicare
                            if (doSIIIRSync) {
                                const schoolResponse = await fetch(leafEditUrl, {
                                    method: "post",
                                    credentials: "same-origin",
                                    headers: {
                                        Accept: "text/html, */*",
                                        "Content-type":
                                            "application/x-www-form-urlencoded; charset=UTF-8",
                                        "X-Requested-With": "XMLHttpRequest"
                                    },
                                    body: new URLSearchParams({
                                        operation: "scoala",
                                        edit: ou,
                                        businessCategory: `cn=${businessCategory},c=ro`
                                    }).toString()
                                });
                                await responseTextOrThrow(schoolResponse, "Actualizarea centrului de comunicare");

                                const siiir_code = items[1];

                                if (siiir_code.match(/^\d+$/)) {
                                    //update cod SIIIR
                                    const siiirResponse = await fetch(siiirCodeEditUrl, {
                                        method: "post",
                                        credentials: "same-origin",
                                        headers: {
                                            Accept: "text/html, */*",
                                            "Content-type":
                                                "application/x-www-form-urlencoded; charset=UTF-8",
                                            "X-Requested-With": "XMLHttpRequest"
                                        },
                                        body: new URLSearchParams({
                                            dn: ou,
                                            siiir_code
                                        }).toString()
                                    });
                                    await responseTextOrThrow(siiirResponse, "Actualizarea codului SIIIR");
                                }
                            }
                            progressCounter++;
                        } catch (err) {
                            console.error(`AUSITools: linia ${lineIndex + 1}: ${err.message}`);
                            errorLines.push(lineIndex + 1);
                            errorcounter++;
                            progressCounter++;
                        }
                    } else {
                        //we only mark error if we are not on the first line
                        if (lineIndex > 0) {
                            errorLines.push(lineIndex + 1);
                            errorcounter++;
                        }
                        progressCounter++;
                    }
                }

                updateProgress(100);
                if (errorcounter == 0) {
                    displaySuccess("Import finalizat cu succes");
                } else {
                    displayWarning(`Import finalizat cu erori. Liniile cu erori: [${errorLines.join(",")}]`);
                }
            } catch (err) {
                displayError(`Import eșuat: ${err.message}`);
            } finally {
                if (timer) {
                    clearInterval(timer);
                }
                setBusy(false);
                event.target.value = null;
            }

        }
        reader.onerror = () => {
            displayError("Import eșuat: fișierul nu a putut fi citit.");
            setBusy(false);
            event.target.value = null;
        }
        reader.onabort = () => {
            displayError("Import anulat.");
            setBusy(false);
            event.target.value = null;
        }
        reader.readAsArrayBuffer(file);
    }

    const patchCSS = (selectorText, styleDeclaration, styleValue) => {
        const CSSStyleSheets = document.styleSheets;
        for (const CSSStyleSheet of CSSStyleSheets) {
            try {
                const cssRules = CSSStyleSheet.cssRules;
                for (const CSSStyleRule of cssRules) {

                    if (!CSSStyleRule.selectorText) {
                        continue;
                    }
                    if (CSSStyleRule.selectorText.trim() == selectorText.trim()) {
                        // console.log(`MATCH: ${CSSStyleRule.selectorText.trim()}`);
                        // console.log(CSSStyleRule.style);
                        // console.log(`Setting ${styleDeclaration} from ${CSSStyleRule.style[styleDeclaration]} to ${styleValue}`);
                        CSSStyleRule.style[styleDeclaration] = styleValue;
                        //console.log(`New value: ${CSSStyleRule.style[styleDeclaration]}`);
                    }
                }
            } catch (err) {
                //console.log(err);

            }
        }
    }

    const addCSS = (css) => {
        const sheet = document.styleSheets[0];
        if (!sheet) {
            return;
        }
        sheet.insertRule(css, sheet.cssRules.length);
    }

    //MARK: SETUP
    const doSetup = async () => {
        const contentDiv = document.getElementById("content");
        const searchDiv = document.getElementById("search");
        if (!contentDiv || !searchDiv) {
            console.warn("AUSITools: pagina AUSI nu conține elementele necesare.");
            return;
        }
        contentDiv.style.width = "auto";
        contentDiv.style.height = "98vh";

        //MARK: CSS PATCHES

        patchCSS("body, html", "background-color", "white");

        patchCSS(".link_meniu", "width", "auto");
        patchCSS(".link_meniu", "display", "flex");
        patchCSS(".link_meniu", "align-items", "center");
        patchCSS(".link_meniu", "justify-content", "center");
        patchCSS(".link_meniu", "height", "1.6rem");
        patchCSS(".link_meniu", "vertical-align", "middle");

        patchCSS("div.extern_ovf", "height", "auto");
        patchCSS("div.extern_ovf", "overflow-y", "scroll");
        patchCSS("div.extern_ovf", "max-width", "700px");
        patchCSS("div.extern_ovf", "min-height", "600px");
        patchCSS("div.extern_ovf", "position", "relative");
        patchCSS("div.extern_ovf", "top", "1rem");
        patchCSS("div.extern_ovf", "bottom", "1rem");
        patchCSS("div.extern_ovf", "border", "1px solid lightgrey");
        patchCSS("div.extern_ovf", "border-radius", "5px");
        patchCSS("div.extern_ovf", "background-color", "transparent");
        patchCSS("div.extern_ovf", "transition", "height .3s ease");

        patchCSS("div.search", "position", "relative");
        patchCSS("div.search", "top", "auto");
        patchCSS("div.search", "border", "1px solid lightgrey");
        patchCSS("div.search", "border-radius", "5px");

        patchCSS("div.edit", "border", "1px solid lightgrey");
        patchCSS("div.edit", "border-radius", "5px");
        patchCSS("div.edit", "width", "auto");
        patchCSS("div.edit", "padding-right", "1rem");

        patchCSS("div.extern", "max-height", "none"); //why the f is auto not a valid value?
        patchCSS("div.extern", "width", "auto");
        patchCSS("div.extern", "border", "0");

        //MARK: REMOVE OLD STUFF
        //remove old links
        const oldLinks = document.getElementsByClassName("link_meniu");
        for (const oldLink of Array.from(oldLinks)) {
            oldLink.parentElement.removeChild(oldLink);
        }

        //remove old menu
        const oldMenu = document.getElementById("div_meniu");
        if (oldMenu) {
            oldMenu.parentElement.removeChild(oldMenu);
        }

        //remove width of all H1
        const h1 = document.getElementsByTagName("h1");
        for (const h of Array.from(h1)) {
            h.style.width = "auto";
        }

        //MARK: LAYOUT
        contentDiv.style.display = "flex";
        contentDiv.style.flexDirection = "column";

        //MARK: IMPORT/EXPORT FORM
        const excelIcon = document.createElement("img");
        excelIcon.src = "../img/excel.png";
        excelIcon.style.height = "10px";
        excelIcon.style.marginRight = "5px";

        const exportLink = document.createElement("a");
        exportLink.className = "btn btn-primary my-3";
        exportLink.href = "#";
        exportLink.onclick = doExport;
        exportLink.textContent = "Export";
        exportLink.prepend(excelIcon);
        exportLink.style.flexGrow = "1";

        const importFileField = document.createElement("input");
        importFileField.type = "file";
        importFileField.className = "form-control";
        importFileField.accept = ".xlsx";
        importFileField.onchange = doImport;

        const importFileFieldDiv = document.createElement("div");
        importFileFieldDiv.className = "m-3";
        importFileFieldDiv.style.flexGrow = "1";
        importFileFieldDiv.appendChild(importFileField);

        const siiirSyncCheckbox = document.createElement("input");
        siiirSyncCheckbox.type = "checkbox";
        siiirSyncCheckbox.id = "siiirSync";
        siiirSyncCheckbox.className = "form-check-input";
        siiirSyncCheckbox.name = "siiirSync";
        siiirSyncCheckbox.checked = false;

        const siiirSyncLabel = document.createElement("label");
        siiirSyncLabel.className = "form-check-label";
        siiirSyncLabel.htmlFor = "siiirSync";
        siiirSyncLabel.textContent = "Sincronizare SIIIR";

        const checkboxDiv = document.createElement("div");
        checkboxDiv.className = "form-check m-3";
        checkboxDiv.style.flexGrow = "1";
        checkboxDiv.appendChild(siiirSyncCheckbox);
        checkboxDiv.appendChild(siiirSyncLabel);

        const importForm = document.createElement("form");
        importForm.id = "ausiToolsForm";
        importForm.onsubmit = (event) => event.preventDefault();
        importForm.className = "mx-5";
        importForm.style.display = "flex";
        importForm.style.width = "700px";

        const messageDiv = document.createElement("div");
        messageDiv.id = "message";
        messageDiv.style.flexGrow = "1";
        messageDiv.className = "alert alert-primary";
        messageDiv.innerText = "Alegeți fisierul pentru import";


        importForm.appendChild(exportLink);
        importForm.appendChild(checkboxDiv);
        importForm.appendChild(importFileFieldDiv);
        importForm.appendChild(messageDiv);

        searchDiv.parentNode.insertBefore(importForm, searchDiv);

        //MARK: progress bar outer div
        const progressBarOuterDiv = document.createElement("div");
        progressBarOuterDiv.id = "progressOuter";
        progressBarOuterDiv.className = "progress";
        progressBarOuterDiv.setAttribute("aria-valuenow", "0");
        progressBarOuterDiv.setAttribute("aria-valuemin", "0");
        progressBarOuterDiv.setAttribute("aria-valuemax", "100");
        progressBarOuterDiv.setAttribute("role", "progressbar");
        progressBarOuterDiv.style.width = "98%";
        progressBarOuterDiv.style.height = "20px";
        progressBarOuterDiv.style.display = "none";
        progressBarOuterDiv.style.position = "fixed";
        progressBarOuterDiv.style.top = "1%";
        progressBarOuterDiv.style.left = "1%";

        //MARK: progress bar inner
        const progressBarInnerDiv = document.createElement("div");
        progressBarInnerDiv.id = "progressInner";
        progressBarInnerDiv.className = "progress-bar bg-info";
        progressBarInnerDiv.style.width = "0%";
        progressBarInnerDiv.textContent = "0%";
        //smooth transition
        progressBarOuterDiv.appendChild(progressBarInnerDiv);
        document.body.appendChild(progressBarOuterDiv);

    }

    doSetup();

})();
