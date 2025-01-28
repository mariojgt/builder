/**
 * Return the string in to a valid date format
 * @param {*} dateToFormat
 *
 * @returns
 */
export const formatDate = (dateToFormat) => {
    // Check if the input is empty
    if (!dateToFormat) {
        return "";
    }

    let finalDate;

    // Check if the date format is ISO (contains 'T')
    if (dateToFormat.includes("T")) {
        finalDate = new Date(dateToFormat);
    } else {
        // Parse as 'dd/mm/yyyy'
        const tempDate = dateToFormat.split("/");
        finalDate = new Date(
            `${tempDate[2]}-${tempDate[1]}-${tempDate[0]}`
        );
    }

    // Format to 'yyyy-mm-dd'
    return finalDate.toISOString().split("T")[0];
};

/**
 * Return the string in to a valid timestamp format
 * @param {*} dateToFormat
 * @returns
 */
export const formatTimestamp = (dateToFormat) => {
    // Cast to a temp date
    const tempDate = dateToFormat.split("/");
    // Cast to datetime-local
    let finalValue = new Date(
        tempDate[2] + "/" + tempDate[1] + "/" + tempDate[0]
    ).toISOString().substr(0, 16);

    return finalValue;
}

/**
 * Make sure the return information is string
 * @param {*} data
 * @returns
 */
export const makeString = (data) => {
    // Return the data as a string
    if (data) {
        return data.toString();
    } else {
        return "";
    }
}
