using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.Linq;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;
using System.IO;
using Newtonsoft.Json;

namespace WindowsFormsApp1
{
    public partial class Form1 : Form
    {
        public string interestContent;
        public string interestFileName;
        public string fullPath;

        #region UI
        public Form1()
        {
            InitializeComponent();
        }
        private void Form1_Load(object sender, EventArgs e)
        {
            button1.Visible = false;
            clearWindowButton.Visible = false;
            interestUploadButton.Visible = false;
        }
        
        private void debugOutput(string strDebugText)
        {
            try
            {
                System.Diagnostics.Debug.Write(strDebugText + Environment.NewLine);
                txtResponse.Text = txtResponse.Text + strDebugText + Environment.NewLine;
                txtResponse.SelectionStart = txtResponse.TextLength;
                txtResponse.ScrollToCaret();
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.Write(ex.Message, ToString() + Environment.NewLine);
            }
        }

        private void showConversionOutput(string strDebugText)
        {
            try
            {
                System.Diagnostics.Debug.Write(strDebugText + Environment.NewLine);
                conversionDataOutput.Text = conversionDataOutput.Text + strDebugText + Environment.NewLine;
                conversionDataOutput.SelectionStart = conversionDataOutput.TextLength;
                conversionDataOutput.ScrollToCaret();
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.Write(ex.Message, ToString() + Environment.NewLine);
            }
        }
        #endregion
        #region buttonClicks
        private void Button1_Click(object sender, EventArgs e)
        {
            string strPath = label4.Text;
            //function call to get the filename
            var filename = Path.GetFileName(strPath);
            var destPath = string.Empty;
            String URL_LOC = Globals.URL_LOCATION + "/FACS_sim_Desktop.php?clientId=" + comboBox1.Text + "&target_file=" + filename + "&mode=" + Globals.MODE;
            destPath = URL_LOC;
            System.Diagnostics.Process.Start(destPath);
        }

        private void ClearWindowButton_Click(object sender, EventArgs e)
        {
            txtResponse.Text = ""; //clears the output window
            conversionDataOutput.Text = ""; //clears data window
            if (comboBox2.Text == "New Account File")
            {
                interestUploadButton.Visible = true;
                textBox1.Text = "File to Convert";
            }
            else if(comboBox2.Text == "Payment File")
            {
                interestUploadButton.Visible = false;
                textBox1.Text = "Payment File to Convert";
            }
            label4.Text = "";
            button1.Visible = false;
            interestUploadButton.Visible = false;
            clearWindowButton.Visible = false;
        }

        private void InterestUploadButton_Click(object sender, EventArgs e)
        {
            var interestFileContent = string.Empty;
            var fExt = string.Empty;
            var interestFilePath = string.Empty;
            var interestFileName = string.Empty;
            var interestFilenameWithoutPath = string.Empty;
        }
        private async void submitButton_ClickAsync(object sender, EventArgs e)
        {
            var fileContent = string.Empty;
            var fExt = string.Empty;
            var filePath = string.Empty;
            var conversionType = string.Empty;
            var filenameWithoutPath = string.Empty;
            label4.Text = "Working...";

            if (comboBox1.Text == "" || comboBox2.Text == "")
            {
                debugOutput("combo empty");
                errorDisplay.Text = "You need to select both the ClientID and Action to Take";
            }
            else
            { //we should have the client ID and the action selected at this point
                errorDisplay.Text = ""; //clear any potential error messages
                //////////////////////////////
                using (OpenFileDialog openFileDialog = new OpenFileDialog())
                {
                    openFileDialog.InitialDirectory = "c:\\";
                    openFileDialog.Filter = "txt files (*.txt)|*.txt|All files (*.*)|*.*";
                    openFileDialog.FilterIndex = 2;
                    openFileDialog.RestoreDirectory = true;

                    if (openFileDialog.ShowDialog() == DialogResult.OK)
                    { //we've selected the file
                        try
                        {
                            fExt = Path.GetExtension(openFileDialog.FileName); //Get the path of specified file
                            filePath = openFileDialog.FileName;
                            filenameWithoutPath = Path.GetFileName(filePath);

                            /*
                            if (label1.Text == "Interest File to Upload")
                            {
                                interestFileName = textBox1.Text;
                            }*/

                            textBox1.Text = filePath;
                            //Read the contents of the file into a stream
                            var fileStream = openFileDialog.OpenFile();
                            using (StreamReader reader = new StreamReader(fileStream))
                            {
                                fileContent = reader.ReadToEnd();
                            }
                            if (conversionType != "Payment") { button1.Visible = true; }
                            clearWindowButton.Visible = true;
                        }
                        catch (Exception ex)
                        {
                            System.Diagnostics.Debug.Write(ex.Message, ToString() + Environment.NewLine);
                        }
                    }
                }
                //////////////////////////////
                var cId = comboBox1.Text;
                conversionType = comboBox2.Text;
                interestContent = txtResponse.Text;
                RestClient rClient = new RestClient(cId, fileContent, fExt, filePath, conversionType, "api", interestContent, interestFileName);
                String URL_LOC = Globals.URL_LOCATION;
                rClient.endPoint = @URL_LOC + "/api/convertjsonAPI.php";
                debugOutput("Rest API Client Created");
                //debugOutput(rClient.endPoint);
                /* working */
                //var strResponse = await rClient.makeFormJsonRequestAsync();
                //var str = System.Text.Encoding.ASCII.GetString(strResponse);
                /* end working */

                //byte[] strResponse = await rClient.makeFormJsonRequestAsync();
                //var str = System.Text.Encoding.ASCII.GetString(strResponse);

                //byte[] strResponse = await rClient.makeFormJsonRequestAsync();
                //var strResponse = await rClient.makeFormJsonRequestAsync();
                var strResponse = await rClient.makeFormJsonRequestAsync();
                var bytesAsString = Encoding.UTF8.GetString(strResponse);

                var thisConversion = JsonConvert.DeserializeObject<conversion>(bytesAsString);
                var aD = thisConversion.accountData;
                var nD = thisConversion.noteData;
                
                /*
                str = str.Replace("\"", ""); //cleanup
                str = str.Replace("\\", ""); //cleanup
                str = str.Replace("#r#n", Environment.NewLine);
                */

                string convertedName = "";
                string convertedNoteName = "";
                
                int recordFileCreatedAlready = 0; //just a flag to set for the output to show whether a regular recordfile has already been create in case a notes file exists
                //string path = Directory.GetCurrentDirectory();
                ///////////////
                if (conversionType == "Payment")
                {
                    convertedName = "GuarPmt_EFS_" + DateTime.Today.ToString("MMddyyyy") + ".txt";
                    aD = cleanTextRecord(aD);
                    createFile(aD, convertedName, 1);
                }
                else
                {
                    if (aD.Length > 0)
                    { //we have the standard account file
                        convertedName = "appConverted_" + filenameWithoutPath;
                        aD = cleanTextRecord(aD);
                        createFile(aD, convertedName, 1);
                        recordFileCreatedAlready = 1;
                    }
                    if (nD != null)
                    {
                        if (nD.Length > 0)
                        { //we have a notes file - so we need to create a separate file and let the user know
                            debugOutput("---NOTE FILE---");
                            convertedNoteName = "appConverted_NoteFile_" + filenameWithoutPath;
                            nD = cleanTextRecord(nD);
                            int showTopLine = 0;
                            if (recordFileCreatedAlready == 1) { showTopLine = 0; } else { showTopLine = 1; }
                            createFile(nD, convertedNoteName, showTopLine);
                        }
                    }
                }
                ///////////////
                /*string[] paths = { @path, convertedName };
                string fullPath = Path.Combine(paths);
                debugOutput(str);

                System.IO.File.WriteAllText(@fullPath, str);
                label4.Text = fullPath;*/
                //////////////////////////////
            }
        }

        private void ComboBox1_SelectedIndexChanged(object sender, EventArgs e)
        {

        }
        #endregion
        #region fileTreatment
        private string cleanTextRecord(string stringToClean)
        {
            stringToClean = stringToClean.Replace("\"", ""); //cleanup
            stringToClean = stringToClean.Replace("\\", ""); //cleanup
            stringToClean = stringToClean.Replace("#r#n", Environment.NewLine);
            var cleaned = stringToClean;
            return cleaned;
        }
        private void createFile(string contentToWrite, string nameOfFile, int writeTopLine)
        {
            string path = Directory.GetCurrentDirectory();
            string[] paths = { @path, nameOfFile };
            string fullPath = Path.Combine(paths);
            //debugOutput(contentToWrite);
            if (writeTopLine == 1) { debugOutput("---------------------------------------------"); }
            //debugOutput(fullPath + nameOfFile + " created ");
            debugOutput(fullPath + " created ");
            showConversionOutput(contentToWrite);
            System.IO.File.WriteAllText(@fullPath, contentToWrite);
            debugOutput("---------------------------------------------");
            label4.Text = fullPath;
        }
        #endregion
        #region commented
        /*
        private async void submitButton_ClickAsync(object sender, EventArgs e)
        {
            var fileContent = string.Empty;
            var fExt = string.Empty;
            var filePath = string.Empty;
            var conversionType = string.Empty;
            var filenameWithoutPath = string.Empty;
            label4.Text = "Working...";

            if (comboBox1.Text == "" || comboBox2.Text == "")
            {
                debugOutput("combo empty");
                errorDisplay.Text = "You need to select both the ClientID and Action to Take";
            }
            else
            { //we should have the client ID and the action selected at this point
                #region buttonClick
                //////////////////////////////
                using (OpenFileDialog openFileDialog = new OpenFileDialog())
                {
                    openFileDialog.InitialDirectory = "c:\\";
                    openFileDialog.Filter = "txt files (*.txt)|*.txt|All files (*.*)|*.*";
                    openFileDialog.FilterIndex = 2;
                    openFileDialog.RestoreDirectory = true;

                    if (openFileDialog.ShowDialog() == DialogResult.OK)
                    { //we've selected the file
                        try
                        {
                            fExt = Path.GetExtension(openFileDialog.FileName); //Get the path of specified file
                            filePath = openFileDialog.FileName;
                            filenameWithoutPath = Path.GetFileName(filePath);

                            if (label1.Text == "Interest File to Upload")
                            {
                                interestFileName = textBox1.Text;
                            }
                            
                            textBox1.Text = filePath;
                            //Read the contents of the file into a stream
                            var fileStream = openFileDialog.OpenFile();
                            using (StreamReader reader = new StreamReader(fileStream))
                            {
                                fileContent = reader.ReadToEnd();
                            }
                            if (conversionType != "Payment") { button1.Visible = true; }
                            clearWindowButton.Visible = true;
                        }
                        catch (Exception ex)
                        {
                            System.Diagnostics.Debug.Write(ex.Message, ToString() + Environment.NewLine);
                        }
                    }
                }
                //////////////////////////////
                var cId = comboBox1.Text;
                conversionType = comboBox2.Text;
                interestContent = txtResponse.Text;
                RestClient rClient = new RestClient(cId, fileContent, fExt, filePath, conversionType, "api", interestContent, interestFileName);
                String URL_LOC = Globals.URL_LOCATION;
                rClient.endPoint = @URL_LOC + "/api/convertjson.php";
                debugOutput("Rest Client Created");
                // working
                //var strResponse = await rClient.makeFormJsonRequestAsync();
                //var str = System.Text.Encoding.ASCII.GetString(strResponse);
                //end working

                //byte[] strResponse = await rClient.makeFormJsonRequestAsync();
                //var str = System.Text.Encoding.ASCII.GetString(strResponse);

                //byte[] strResponse = await rClient.makeFormJsonRequestAsync();
                //var strResponse = await rClient.makeFormJsonRequestAsync();
                var strResponse = await rClient.makeFormJsonRequestAsync();
                var bytesAsString = Encoding.UTF8.GetString(strResponse);
                var thisConversion= JsonConvert.DeserializeObject<conversion>(bytesAsString);

                var aD = thisConversion.accountData;
                var nD = thisConversion.noteData;

                
                //str = str.Replace("\"", ""); //cleanup
                //str = str.Replace("\\", ""); //cleanup
                //str = str.Replace("#r#n", Environment.NewLine);
                

                string convertedName = "";
                string convertedNoteName = "";
                string str = "";
                string path = Directory.GetCurrentDirectory();
                ///////////////
                if (conversionType == "Payment")
                {
                    convertedName = "GuarPmt_EFS_" + DateTime.Today.ToString("MMddyyyy") + ".txt";
                }
                else
                {
                    if ( aD.Length > 0 ) { //we have the standard account file
                        convertedName = "appConverted_" + filenameWithoutPath;
                        aD = cleanTextRecord(aD);
                        str = aD;
                    }
                    if (nD.Length > 0 ) { //we have a notes file - so we need to create a separate file and let the user know
                        convertedNoteName = "appConverted_NoteFile_" + filenameWithoutPath;
                        nD = cleanTextRecord(nD);
                        str = nD;
                    }
                }
                ///////////////
                string[] paths = { @path, convertedName };
                string fullPath = Path.Combine(paths);
                debugOutput(str);

                System.IO.File.WriteAllText(@fullPath, str);
                label4.Text = fullPath;
                //////////////////////////////
                #endregion
            }
        }
        */
        /*
        private void Button1_Click(object sender, EventArgs e)
        {
            string strPath = label4.Text;
            //function call to get the filename
            var filename = Path.GetFileName(strPath);
            var destPath = string.Empty;
            String URL_LOC = Globals.URL_LOCATION + "/FACS_sim_Desktop.php?clientId=" + comboBox1.Text + "&target_file=" + filename + "&mode=" + Globals.MODE;
            destPath = URL_LOC;
            System.Diagnostics.Process.Start(destPath);
        }

        private void ClearWindowButton_Click(object sender, EventArgs e)
        {
            txtResponse.Text = ""; //clears the output window
        }*/
        #endregion
        #region changeActions
        private void TextBox2_TextChanged(object sender, EventArgs e)
        {

        }

        private void TextBox2_TextChanged_1(object sender, EventArgs e)
        {

        }

        private void ComboBox2_SelectedIndexChanged(object sender, EventArgs e)
        {
            if(comboBox2.Text == "New Account File"){
                interestUploadButton.Visible = true;
                textBox1.Text = "File to Convert";
            }
            else if(comboBox2.Text == "Payment File")
            {
                interestUploadButton.Visible = false;
                textBox1.Text = "Payment File to Convert";
            }
        }

        private void TextBox1_TextChanged(object sender, EventArgs e)
        {

        }
        #endregion
    }
}
#region globals
public static class Globals
{
    ///public static String MODE = "dev";
    //public static String URL_LOCATION = "http://127.0.0.1/efs"; // dev
    public static String MODE = "prod";
    public static String URL_LOCATION = "http://192.168.1.3"; // prod
}
#endregion
