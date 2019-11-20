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

namespace WindowsFormsApp1
{
    public partial class Form1 : Form
    {
        public string interestContent;
        public string interestFileName;
        public string fullPath;

        public Form1()
        {
            InitializeComponent();
        }

        private void Form1_Load(object sender, EventArgs e)
        {
            button1.Visible = false;
            clearWindowButton.Visible = false;
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

                byte[] strResponse = await rClient.makeFormJsonRequestAsync();
                var str = System.Text.Encoding.ASCII.GetString(strResponse);

                str = str.Replace("\"", ""); //cleanup
                str = str.Replace("\\", ""); //cleanup
                str = str.Replace("#r#n", Environment.NewLine);

                var convertedName = "";
                ///////////////
                if (conversionType == "Payment")
                {
                    convertedName = "GuarPmt_EFS_" + DateTime.Today.ToString("MMddyyyy") + ".txt";
                }
                else
                {
                    convertedName = "converted_" + filenameWithoutPath;
                }
                ///////////////
                string path = Directory.GetCurrentDirectory();
                string[] paths = { @path, convertedName };
                string fullPath = Path.Combine(paths);
                debugOutput(str);

                System.IO.File.WriteAllText(@fullPath, str);
                label4.Text = fullPath;
                //////////////////////////////
                #endregion
            }
        }

        #region debugOutput
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
        #endregion

        private void InterestUploadButton_Click(object sender, EventArgs e)
        {
            var interestFileContent = string.Empty;
            var fExt = string.Empty;
            var interestFilePath = string.Empty;
            var interestFileName = string.Empty;
            var interestFilenameWithoutPath = string.Empty;
        }

        private void ComboBox1_SelectedIndexChanged(object sender, EventArgs e)
        {

        }

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
        }
    }
}

public static class Globals
{
    public static String MODE = "dev";
    public static String URL_LOCATION = "http://127.0.0.1/efs"; // dev
    //public static String MODE = "prod";
    //public static String URL_LOCATION = "http://192.168.1.3"; // prod
}
