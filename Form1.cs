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
        public Form1()
        {
            InitializeComponent();
        }

        private void Form1_Load(object sender, EventArgs e)
        {

        }

        
        private async void submitButton_ClickAsync(object sender, EventArgs e)
        {
            var fileContent = string.Empty;
            var fExt = string.Empty;
            var filePath = string.Empty;
            var conversionType = string.Empty;
            var filenameWithoutPath = string.Empty;
            
            if(comboBox1.Text == "" || comboBox2.Text == "")
            {
                debugOutput("combo empty");
                errorDisplay.Text = "You need to select both the ClientID and Action to Take";
            }
            else
            {
                #region buttonClick
                //////////////////////////////
                using (OpenFileDialog openFileDialog = new OpenFileDialog())
                {
                    openFileDialog.InitialDirectory = "c:\\";
                    openFileDialog.Filter = "txt files (*.txt)|*.txt|All files (*.*)|*.*";
                    openFileDialog.FilterIndex = 2;
                    openFileDialog.RestoreDirectory = true;

                    if (openFileDialog.ShowDialog() == DialogResult.OK)
                    {
                        //Get the path of specified file
                        fExt = Path.GetExtension(openFileDialog.FileName);
                        filePath = openFileDialog.FileName;
                        filenameWithoutPath = Path.GetFileName(filePath);
                        textBox1.Text = filePath;

                        //Read the contents of the file into a stream
                        var fileStream = openFileDialog.OpenFile();
                        using (StreamReader reader = new StreamReader(fileStream))
                        {
                            fileContent = reader.ReadToEnd();
                        }
                    }
                }
                //////////////////////////////
                var cId = comboBox1.Text;
                conversionType = comboBox2.Text;
                debugOutput(cId);
                RestClient rClient = new RestClient(cId, fileContent, fExt, filePath, conversionType, "api");
                rClient.endPoint = "http://127.0.0.1/efs/api/convertjson.php";
                debugOutput("Rest Client Created");

                byte[] strResponse = await rClient.makeFormJsonRequestAsync();
                //byte[] bytes = ASCIIEncoding.GetBytes(strResponse);
                var str = System.Text.Encoding.ASCII.GetString(strResponse);

                /*
                // Convert Unicode to Bytes

                byte[] uni = Encoding.Unicode.GetBytes(str);

                // Convert to ASCII

                string Ascii = Encoding.ASCII.GetString(uni);
                //string uni = Encoding.Unicode.GetBytes(strResponse);

                // Convert to ASCII
                */

                //var str = System.Text.Encoding.Default.GetString(strResponse);

                str = str.Replace("\"", ""); //cleanup
                str = str.Replace("\\", ""); //cleanup
                //str = str.Replace("u2019", "'"); //cleanup


                var convertedName = "converted_" + filenameWithoutPath;
                string path = Directory.GetCurrentDirectory();
                string[] paths = { @path, convertedName };
                string fullPath = Path.Combine(paths);
                debugOutput(str);
                //debugOutput(fullPath);

                System.IO.File.WriteAllText(@fullPath, str);
                label4.Text = "Export file created at:\\r\\n" + fullPath;
                //////////////////////////////
                #endregion
            }
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
    }
}
